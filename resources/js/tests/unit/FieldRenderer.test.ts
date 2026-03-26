import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach } from 'vitest'
import FieldRenderer from '../../Components/Editor/FieldRenderer.vue'

const cases = [
    { type: 'text', selector: 'input[type="text"]' },
    { type: 'textarea', selector: 'textarea' },
    { type: 'url', selector: 'input[type="url"]' },
    { type: 'color', selector: 'input[type="color"]' },
    {
        type: 'select',
        selector: 'select',
        field: { key: 'x', type: 'select', label: 'X', options: ['a', 'b'] },
    },
]

describe('FieldRenderer', () => {
    afterEach(() => {
        vi.useRealTimers()
    })

    cases.forEach(({ type, selector, field }) => {
        it(`renders ${selector} for type "${type}"`, () => {
            const f = field ?? { key: 'x', type, label: 'X' }
            const wrapper = mount(FieldRenderer, {
                props: { field: f, modelValue: type === 'color' ? '#000000' : '' },
            })
            expect(wrapper.find(selector).exists()).toBe(true)
        })
    })

    it('emits update:modelValue on input', async () => {
        vi.useFakeTimers()
        const wrapper = mount(FieldRenderer, {
            props: { field: { key: 'x', type: 'text', label: 'X' }, modelValue: '' },
        })
        await wrapper.find('input').setValue('hello')
        await vi.advanceTimersByTimeAsync(500)
        expect(wrapper.emitted('update:modelValue')[0][0]).toBe('hello')
    })

    it('renders repeater select and custom field visibility for social', async () => {
        const wrapper = mount(FieldRenderer, {
            props: {
                field: {
                    key: 'links',
                    type: 'repeater',
                    label: 'Enlaces',
                    subfields: [
                        { key: 'network', type: 'select', label: 'Red', options: ['instagram', 'otra'] },
                        { key: 'custom_network', type: 'text', label: 'Nombre de la red' },
                        { key: 'url', type: 'url', label: 'URL' },
                    ],
                },
                modelValue: [{ network: 'instagram', custom_network: '', url: 'https://example.com' }],
            },
        })

        expect(wrapper.find('select').exists()).toBe(true)
        expect(wrapper.text()).not.toContain('Nombre de la red')
        await wrapper.find('select').setValue('otra')
        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
})
