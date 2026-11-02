import type { ItemKind, Pipe } from './types'

type ErrorBody = {
  message?: string
  errors?: Record<string, string[]>
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (init.body) {
    headers.set('Content-Type', 'application/json')
  }

  let response: Response
  try {
    response = await fetch(path, { ...init, headers })
  } catch {
    throw new Error('Kunde inte nå API:t. Kör backend på port 8000.')
  }

  const body = (await response.json().catch(() => null)) as (T & ErrorBody) | null

  if (!response.ok) {
    const first = body?.errors ? Object.values(body.errors).flat()[0] : undefined
    throw new Error(first || body?.message || 'Något gick fel.')
  }

  return body as T
}

export function listPipes(): Promise<Pipe[]> {
  return request<{ data: Pipe[] }>('/api/projects').then((body) => body.data)
}

export function createPipe(name: string): Promise<Pipe> {
  return request<{ data: Pipe }>('/api/projects', {
    method: 'POST',
    body: JSON.stringify({ name }),
  }).then((body) => body.data)
}

export function createItem(
  projectId: number,
  input: { title: string; kind: ItemKind; occurs_at?: string },
): Promise<Pipe> {
  return request<{ data: Pipe }>(`/api/projects/${projectId}/items`, {
    method: 'POST',
    body: JSON.stringify(input),
  }).then((body) => body.data)
}

export function moveItem(projectId: number, itemId: number, direction: 'up' | 'down'): Promise<Pipe> {
  return request<{ data: Pipe }>(`/api/projects/${projectId}/items/${itemId}/move`, {
    method: 'POST',
    body: JSON.stringify({ direction }),
  }).then((body) => body.data)
}

export function completeItem(projectId: number, itemId: number): Promise<Pipe> {
  return request<{ data: Pipe }>(`/api/projects/${projectId}/items/${itemId}/complete`, {
    method: 'POST',
  }).then((body) => body.data)
}
