import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockCard from '../../Components/Editor/BlockCard.vue'

const block = { id: 1, type: 'text', props: { content: 'Hi' }, is_active: true, order: 0 }

describe('BlockCard', () => {
    it('shows block type label', () => {
        expect(mount(BlockCard, { props: { block } }).text()).toContain('text')
    })

    it('emits delete when delete button clicked', async () => {
        const wrapper = mount(BlockCard, { props: { block } })
        await wrapper.find('[data-action="delete"]').trigger('click')
        expect(wrapper.emitted('delete')).toBeTruthy()
    })

    it('emits toggle when toggle button clicked', async () => {
        const wrapper = mount(BlockCard, { props: { block } })
        await wrapper.find('[data-action="toggle"]').trigger('click')
        expect(wrapper.emitted('toggle')).toBeTruthy()
    })

    it('emits duplicate when duplicate button clicked', async () => {
        const wrapper = mount(BlockCard, { props: { block } })
        await wrapper.find('[data-action="duplicate"]').trigger('click')
        expect(wrapper.emitted('duplicate')).toBeTruthy()
    })

    it('shows inactive indicator when is_active is false', () => {
        const wrapper = mount(BlockCard, { props: { block: { ...block, is_active: false } } })
        expect(wrapper.find('[data-inactive]').exists()).toBe(true)
    })
})
