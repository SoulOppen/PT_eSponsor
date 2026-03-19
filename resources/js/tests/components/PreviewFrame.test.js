import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import PreviewFrame from '../../Components/Preview/PreviewFrame.vue'

describe('PreviewFrame', () => {
    it('renders a block for each active block', () => {
        const blocks = [
            { id: 1, type: 'text', props: { content: 'Hello' }, is_active: true, order: 0 },
            { id: 2, type: 'image', props: { url: '/img.jpg', alt: '' }, is_active: true, order: 1 },
        ]
        const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
        expect(wrapper.find('[data-block-type="text"]').exists()).toBe(true)
        expect(wrapper.find('[data-block-type="image"]').exists()).toBe(true)
    })

    it('does not render inactive blocks', () => {
        const blocks = [{ id: 1, type: 'text', props: { content: 'Hidden' }, is_active: false, order: 0 }]
        const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
        expect(wrapper.find('[data-block-type="text"]').exists()).toBe(false)
    })

    it('renders blocks in order asc', () => {
        const blocks = [
            { id: 2, type: 'image', props: { url: '/b.jpg', alt: '' }, is_active: true, order: 1 },
            { id: 1, type: 'text', props: { content: 'First' }, is_active: true, order: 0 },
        ]
        const wrapper = mount(PreviewFrame, { props: { blocks, site: { name: '', bio: '' } } })
        const rendered = wrapper.findAll('[data-block-type]')
        expect(rendered[0].attributes('data-block-type')).toBe('text')
        expect(rendered[1].attributes('data-block-type')).toBe('image')
    })
})
