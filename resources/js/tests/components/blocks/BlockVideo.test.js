import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockVideo from '../../../Components/Blocks/BlockVideo.vue'

describe('BlockVideo', () => {
    it('renders a YouTube embed', () => {
        const wrapper = mount(BlockVideo, {
            props: { props: { url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' } },
        })
        expect(wrapper.find('iframe').attributes('src')).toContain('youtube.com/embed')
    })

    it('renders a TikTok embed', () => {
        const wrapper = mount(BlockVideo, {
            props: { props: { url: 'https://www.tiktok.com/@user/video/123' } },
        })
        expect(wrapper.find('[data-tiktok]').exists()).toBe(true)
    })
})
