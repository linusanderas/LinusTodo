import { useEffect, useState } from 'react'
import { createPipe, listPipes } from './api'
import { PipeColumn } from './PipeColumn'
import type { Pipe } from './types'

export default function App() {
  const [pipes, setPipes] = useState<Pipe[] | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [name, setName] = useState('')

  useEffect(() => {
    let active = true
    listPipes()
      .then((next) => {
        if (active) setPipes(next)
      })
      .catch((reason: unknown) => {
        if (active) setError(reason instanceof Error ? reason.message : 'Något gick fel.')
      })
    return () => {
      active = false
    }
  }, [])

  async function run(action: () => Promise<void>): Promise<boolean> {
    setBusy(true)
    setError(null)
    try {
      await action()
      return true
    } catch (reason: unknown) {
      setError(reason instanceof Error ? reason.message : 'Något gick fel.')
      return false
    } finally {
      setBusy(false)
    }
  }

  function replacePipe(pipe: Pipe) {
    setPipes((current) => (current ?? []).map((item) => (item.id === pipe.id ? pipe : item)))
  }

  return (
    <div className="page">
      <header className="top">
        <div>
          <h1>LinusTodo</h1>
          <p className="lede">
            Varje projekt är ett rör. Du jobbar med det som ligger längst ner. När det är klart faller det ur,
            och det som låg ovanför blir nästa.
          </p>
        </div>
        <form
          className="new-pipe"
          onSubmit={(event) => {
            event.preventDefault()
            const next = name.trim()
            if (next === '') return
            void run(async () => {
              const pipe = await createPipe(next)
              setPipes((current) => [...(current ?? []), pipe])
              setName('')
            })
          }}
        >
          <label>
            Nytt rör
            <input
              value={name}
              onChange={(event) => setName(event.target.value)}
              maxLength={80}
              required
              disabled={busy}
            />
          </label>
          <button type="submit" disabled={busy || name.trim() === ''}>
            Lägg till
          </button>
        </form>
      </header>

      {error && (
        <p className="error" role="alert">
          {error}
        </p>
      )}

      <p className="note">Nya saker läggs överst. Flytta ner dem om de ska göras tidigare.</p>

      {pipes === null && !error && <p>Laddar rör…</p>}
      {pipes !== null && pipes.length === 0 && <p>Inga rör ännu. Lägg till ett projekt.</p>}
      {pipes !== null && pipes.length > 0 && (
        <div className="board">
          {pipes.map((pipe) => (
            <PipeColumn key={pipe.id} pipe={pipe} busy={busy} run={run} onChange={replacePipe} />
          ))}
        </div>
      )}
    </div>
  )
}
