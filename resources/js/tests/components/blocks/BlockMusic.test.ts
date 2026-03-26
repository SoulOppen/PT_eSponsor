import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockMusic from '../../../Components/Blocks/BlockMusic.vue'

describe('BlockMusic', () => {
    it('renders a Spotify embed for spotify platform', () => {
        const wrapper = mount(BlockMusic, {
            props: { props: { platform: 'spotify', url: 'https://open.spotify.com/track/123' } },
        })
        expect(wrapper.find('iframe').attributes('src')).toContain('spotify')
    })

    it('renders a Bandcamp embed for bandcamp platform', () => {
        const wrapper = mount(BlockMusic, {
            props: { props: { platform: 'bandcamp', url: 'https://artist.bandcamp.com/track/x' } },
        })
        expect(wrapper.find('iframe').attributes('src')).toContain('bandcamp')
    })
})
