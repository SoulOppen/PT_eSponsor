import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
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
        const wrapper = mount(FieldRenderer, {
            props: { field: { key: 'x', type: 'text', label: 'X' }, modelValue: '' },
        })
        await wrapper.find('input').setValue('hello')
        expect(wrapper.emitted('update:modelValue')[0][0]).toBe('hello')
    })
})
