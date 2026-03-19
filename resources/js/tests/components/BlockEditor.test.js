import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockEditor from '../../Components/Editor/BlockEditor.vue'

const textSchema = {
    fields: [
        { key: 'content', type: 'textarea', label: 'Contenido', required: true },
        { key: 'align', type: 'select', label: 'Alineación', options: ['left', 'center', 'right'] },
    ],
}

describe('BlockEditor', () => {
    it('renders one input per schema field', () => {
        const wrapper = mount(BlockEditor, {
            props: { schema: textSchema, modelValue: { content: '', align: 'left' } },
        })
        expect(wrapper.find('textarea').exists()).toBe(true)
        expect(wrapper.find('select').exists()).toBe(true)
    })

    it('emits update:modelValue when a field changes', async () => {
        const wrapper = mount(BlockEditor, {
            props: { schema: textSchema, modelValue: { content: '', align: 'left' } },
        })
        await wrapper.find('textarea').setValue('Hello')
        expect(wrapper.emitted('update:modelValue')[0][0].content).toBe('Hello')
    })
})
