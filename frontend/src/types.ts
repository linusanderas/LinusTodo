export type ItemKind = 'todo' | 'meeting' | 'task'

export type PipeItem = {
  id: number
  title: string
  kind: ItemKind
  occurs_at: string | null
  position: number
  is_bottom: boolean
}

export type Pipe = {
  id: number
  name: string
  position: number
  items: PipeItem[]
}
