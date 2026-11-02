import { useState } from 'react'
import { completeItem, createItem, moveItem } from './api'
import type { ItemKind, Pipe, PipeItem } from './types'

type Run = (action: () => Promise<void>) => Promise<boolean>

type Props = {
  pipe: Pipe
  busy: boolean
  run: Run
  onChange: (pipe: Pipe) => void
}

const timeFormatter = new Intl.DateTimeFormat('sv-SE', {
  timeZone: 'Europe/Stockholm',
  weekday: 'short',
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
})

function formatWhen(iso: string): string {
  return timeFormatter.format(new Date(iso))
}

function kindLabel(kind: ItemKind): string | null {
  if (kind === 'meeting') return 'Möte'
  if (kind === 'task') return 'Uppgift med tid'
  return null
}

export function PipeColumn({ pipe, busy, run, onChange }: Props) {
  return (
    <section className="pipe" aria-labelledby={`pipe-${pipe.id}`}>
      <h2 id={`pipe-${pipe.id}`}>{pipe.name}</h2>
      <div className="pipe-forms">
        <TodoForm pipeId={pipe.id} busy={busy} run={run} onChange={onChange} />
        <TimeBoundForm pipeId={pipe.id} busy={busy} run={run} onChange={onChange} />
      </div>
      <div className="pipe-stack">
        {pipe.items.length === 0 ? (
          <p className="empty">Röret är tomt.</p>
        ) : (
          pipe.items.map((item, index) => (
            <ItemCard
              key={item.id}
              pipeId={pipe.id}
              item={item}
              isTop={index === 0}
              busy={busy}
              run={run}
              onChange={onChange}
            />
          ))
        )}
      </div>
    </section>
  )
}

function TodoForm({ pipeId, busy, run, onChange }: Omit<Props, 'pipe'> & { pipeId: number }) {
  const [title, setTitle] = useState('')

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault()
        const next = title.trim()
        if (next === '') return
        void run(async () => {
          onChange(await createItem(pipeId, { title: next, kind: 'todo' }))
          setTitle('')
        })
      }}
    >
      <h3>Ny uppgift</h3>
      <label>
        Titel
        <input
          value={title}
          onChange={(event) => setTitle(event.target.value)}
          maxLength={200}
          required
          disabled={busy}
        />
      </label>
      <button type="submit" disabled={busy || title.trim() === ''}>
        Lägg till
      </button>
    </form>
  )
}

function TimeBoundForm({ pipeId, busy, run, onChange }: Omit<Props, 'pipe'> & { pipeId: number }) {
  const [title, setTitle] = useState('')
  const [kind, setKind] = useState<Extract<ItemKind, 'meeting' | 'task'>>('meeting')
  const [occursAt, setOccursAt] = useState('')

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault()
        const next = title.trim()
        if (next === '' || occursAt === '') return
        void run(async () => {
          onChange(await createItem(pipeId, { title: next, kind, occurs_at: occursAt }))
          setTitle('')
          setOccursAt('')
        })
      }}
    >
      <h3>Tidsbunden</h3>
      <label>
        Titel
        <input
          value={title}
          onChange={(event) => setTitle(event.target.value)}
          maxLength={200}
          required
          disabled={busy}
        />
      </label>
      <label>
        Typ
        <select
          value={kind}
          onChange={(event) => setKind(event.target.value as 'meeting' | 'task')}
          disabled={busy}
        >
          <option value="meeting">Möte</option>
          <option value="task">Uppgift med tid</option>
        </select>
      </label>
      <label>
        Datum och tid
        <input
          type="datetime-local"
          value={occursAt}
          onChange={(event) => setOccursAt(event.target.value)}
          required
          disabled={busy}
        />
      </label>
      <button type="submit" disabled={busy || title.trim() === '' || occursAt === ''}>
        Lägg till
      </button>
    </form>
  )
}

function ItemCard({
  pipeId,
  item,
  isTop,
  busy,
  run,
  onChange,
}: {
  pipeId: number
  item: PipeItem
  isTop: boolean
  busy: boolean
  run: Run
  onChange: (pipe: Pipe) => void
}) {
  const label = kindLabel(item.kind)
  const timeBound = item.kind !== 'todo'
  const className = ['item', timeBound ? 'timebound' : '', item.is_bottom ? 'bottom' : ''].filter(Boolean).join(' ')

  return (
    <article className={className}>
      {label && <p className="kicker">{label}</p>}
      <h3>{item.title}</h3>
      {item.occurs_at && <p className="when">{formatWhen(item.occurs_at)}</p>}
      {timeBound && !item.is_bottom && (
        <p className="hint">Allt under den här tiden ska vara klart innan dess.</p>
      )}
      {item.is_bottom && <p className="working">Det här jobbar du med</p>}
      <div className="item-actions">
        <button
          type="button"
          className="secondary"
          disabled={busy || isTop}
          onClick={() => void run(async () => onChange(await moveItem(pipeId, item.id, 'up')))}
        >
          Flytta upp
        </button>
        <button
          type="button"
          className="secondary"
          disabled={busy || item.is_bottom}
          onClick={() => void run(async () => onChange(await moveItem(pipeId, item.id, 'down')))}
        >
          Flytta ner
        </button>
        {item.is_bottom && (
          <button
            type="button"
            className="primary"
            disabled={busy}
            onClick={() => void run(async () => onChange(await completeItem(pipeId, item.id)))}
          >
            {timeBound ? 'Passerad' : 'Klar'}
          </button>
        )}
      </div>
      {item.is_bottom && <p className="hint">Den faller ur röret.</p>}
    </article>
  )
}
