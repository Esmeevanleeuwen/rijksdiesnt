import { afterEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import MapView from './MapView.vue'

const wrappers: ReturnType<typeof mount>[] = []
function render() {
  const wrapper = mount(MapView)
  wrappers.push(wrapper)
  return wrapper
}
afterEach(() => { wrappers.forEach(wrapper => wrapper.unmount()); wrappers.length = 0 })

describe('regionale voorbeeldkaart', () => {
  it('shows local geography, a selected Amsterdam popup and explicit demo attribution', () => {
    const wrapper = render()
    expect(wrapper.find('.region-geography image').attributes('href')).toBe('/images/netherlands-map.svg')
    expect(wrapper.find('.region-popup').text()).toContain('Gemeente Amsterdam')
    expect(wrapper.findAll('.region-marker')).toHaveLength(24)
    expect(wrapper.text()).toContain('Schematische kaart · voorbeeldlocaties')
    expect(wrapper.find('.region-attribution').text()).toBe('MapSVG · CC BY 4.0')
  })

  it('searches for a city, changes the viewport and opens the selected details', async () => {
    const wrapper = render()
    const originalBounds = wrapper.find('.region-geography').attributes('viewBox')
    await wrapper.get('#location-search').setValue('Utrecht')
    await wrapper.get('form').trigger('submit')
    expect(wrapper.findAll('.region-marker')).toHaveLength(3)
    expect(wrapper.find('.region-popup').text()).toContain('Gemeente Utrecht')
    expect(wrapper.find('.region-geography').attributes('viewBox')).not.toBe(originalBounds)
    await wrapper.get('.region-details-link').trigger('click')
    expect(wrapper.emitted('open-organization')).toEqual([['Gemeente Utrecht']])
  })

  it('combines province, organization type and government level filters and resets them', async () => {
    const wrapper = render()
    await wrapper.get('select[aria-label="Provincie"]').setValue('Noord-Holland')
    await wrapper.get('select[aria-label="Organisatietype"]').setValue('Gemeente')
    await wrapper.get('select[aria-label="Bestuurslaag"]').setValue('Lokaal')
    expect(wrapper.findAll('.region-marker')).toHaveLength(3)
    expect(wrapper.findAll('.region-marker').every(item => item.attributes('aria-label')?.startsWith('Gemeente'))).toBe(true)
    await wrapper.get('button[aria-label="Kaart herstellen"]').trigger('click')
    expect(wrapper.findAll('.region-marker')).toHaveLength(24)
    expect((wrapper.get('select[aria-label="Provincie"]').element as HTMLSelectElement).value).toBe('')
  })

  it('handles a missing location without retaining a misleading selection', async () => {
    const wrapper = render()
    await wrapper.get('#location-search').setValue('niet-bestaande-plaats')
    await wrapper.get('form').trigger('submit')
    expect(wrapper.findAll('.region-marker')).toHaveLength(0)
    expect(wrapper.find('.region-popup').exists()).toBe(false)
    expect(wrapper.find('[role="status"]').text()).toContain('Geen voorbeeldlocaties gevonden')
  })

  it('lets users select locations by keyboard and zoom with the map controls', async () => {
    const wrapper = render()
    await wrapper.get('[aria-label="Gemeente Eindhoven, voorbeeldlocatie Eindhoven"]').trigger('keydown', { key: 'Enter' })
    expect(wrapper.find('.region-popup').text()).toContain('Gemeente Eindhoven')
    const originalBounds = wrapper.find('.region-geography').attributes('viewBox')
    await wrapper.get('button[aria-label="Inzoomen"]').trigger('click')
    expect(wrapper.find('.region-geography').attributes('viewBox')).not.toBe(originalBounds)
    await wrapper.get('button[aria-label="Uitzoomen"]').trigger('click')
    expect(wrapper.find('.region-geography').attributes('viewBox')).toBe(originalBounds)
  })
})
