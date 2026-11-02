import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import OrganizationsView from './OrganizationsView.vue'
import type { Organization } from '../api'

const organization: Organization = {
  id: 'a0000000-0000-4000-8000-000000000001', official_name: 'Gemeente Voorbeeldstad', display_name: null,
  organization_type: 'Gemeente', description: 'Fictieve gemeente voor deze demonstratie.', website_url: null,
  provenance: [{ name: 'Lokale voorbeeldgegevens', url: null, external_id: 'demo-gemeente', retrieved_at: '2026-01-01T12:00:00Z', is_demo: true }],
}
function response(data: unknown) { return { ok: true, json: async () => data } as Response }
const wrappers: ReturnType<typeof mount>[] = []
function render(props: { organizationId?: string; initialQuery?: string } = {}) {
  const wrapper = mount(OrganizationsView, { props })
  wrappers.push(wrapper)
  return wrapper
}
beforeEach(() => { localStorage.clear(); window.history.replaceState(null, '', '/#/organisaties') })
afterEach(() => { wrappers.splice(0).forEach(wrapper => wrapper.unmount()); vi.unstubAllGlobals() })

describe('organisatiepagina volgens het schermvoorbeeld', () => {
  it('renders the design profile without pretending it has verified identifiers or contacts', async () => {
    const fetch = vi.fn()
    vi.stubGlobal('fetch', fetch)
    const wrapper = render({ organizationId: 'bzk' })
    await flushPromises()
    expect(wrapper.get('h1').text()).toContain('Ministerie van Binnenlandse Zaken')
    expect(wrapper.text()).toContain('Ontwerpvoorbeeld')
    expect(wrapper.text()).toContain('Nog niet gekoppeld')
    expect(wrapper.text()).toContain('Adres nog niet geverifieerd')
    expect(fetch).not.toHaveBeenCalled()
    const tab = wrapper.findAll('[role="tab"]').find(item => item.text() === "Data & API's")!
    await tab.trigger('click')
    expect(wrapper.get('[role="tabpanel"]').text()).toContain('geen geverifieerde datasets')
    await wrapper.get('.org-inline-link').trigger('click')
    expect(wrapper.emitted('navigate')?.at(-1)).toEqual(['/data'])
  })

  it('stores a bookmark shared with Mijn overzicht and removes it again', async () => {
    const changed = vi.fn()
    window.addEventListener('saved-organizations-changed', changed)
    const wrapper = render({ organizationId: 'bzk' })
    await wrapper.get('.org-save').trigger('click')
    expect(JSON.parse(localStorage.getItem('overheidsinformatie.saved')!)).toEqual([{ id: 'bzk', name: 'Ministerie van Binnenlandse Zaken en Koninkrijksrelaties' }])
    expect(wrapper.get('.org-save').attributes('aria-pressed')).toBe('true')
    expect(changed).toHaveBeenCalledOnce()
    await wrapper.get('.org-save').trigger('click')
    expect(JSON.parse(localStorage.getItem('overheidsinformatie.saved')!)).toEqual([])
    window.removeEventListener('saved-organizations-changed', changed)
  })

  it('loads actual demo organizations from the backend, encodes searches and navigates to a profile', async () => {
    const fetch = vi.fn().mockResolvedValue(response({ data: [organization], meta: { total: 1, data_mode: 'demo' } }))
    vi.stubGlobal('fetch', fetch)
    const wrapper = render()
    expect(wrapper.text()).toContain('Organisaties laden')
    await flushPromises()
    expect(wrapper.text()).toContain('Gemeente Voorbeeldstad')
    expect(wrapper.findAll('.org-directory-card')).toHaveLength(2)
    await wrapper.findAll('.org-directory-card')[1]!.trigger('click')
    expect(wrapper.emitted('navigate')?.at(-1)).toEqual([`/organisatie/${organization.id}`])
    await wrapper.get('input[type="search"]').setValue('water & klimaat')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(fetch).toHaveBeenLastCalledWith('/api/v1/organizations?q=water%20%26%20klimaat', expect.objectContaining({ signal: expect.any(AbortSignal) }))
  })

  it('loads a backend profile with raw source provenance and handles errors with retry', async () => {
    const fetch = vi.fn().mockRejectedValueOnce(new TypeError('Failed to fetch')).mockResolvedValueOnce(response({ data: organization, meta: { data_mode: 'demo' } }))
    vi.stubGlobal('fetch', fetch)
    const wrapper = render({ organizationId: organization.id })
    await flushPromises()
    expect(wrapper.get('[role="alert"]').text()).toContain('Geen verbinding')
    await wrapper.get('.org-error button').trigger('click')
    await flushPromises()
    expect(wrapper.get('.org-provenance').text()).toContain('demo-gemeente')
    expect(wrapper.get('.org-provenance').text()).toContain('1 januari 2026')
    expect(wrapper.get('.org-provenance').text()).toContain('Demobron')
    expect(wrapper.get('h1').text()).toBe('Gemeente Voorbeeldstad')
  })

  it('ignores a stale search result and aborts its request', async () => {
    let resolveFirst!: (value: Response) => void
    const fetch = vi.fn().mockImplementationOnce(() => new Promise<Response>(resolve => { resolveFirst = resolve })).mockResolvedValueOnce(response({ data: [], meta: { total: 0, data_mode: 'demo' } }))
    vi.stubGlobal('fetch', fetch)
    const wrapper = render()
    await wrapper.get('input[type="search"]').setValue('onvindbaar')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Geen organisaties gevonden')
    expect(fetch.mock.calls[0]?.[1].signal.aborted).toBe(true)
    resolveFirst(response({ data: [organization], meta: { total: 1, data_mode: 'demo' } }))
    await flushPromises()
    expect(wrapper.findAll('.org-directory-card')).toHaveLength(0)
  })
})
