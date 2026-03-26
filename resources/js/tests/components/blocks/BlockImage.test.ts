import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockImage from '../../../Components/Blocks/BlockImage.vue'

describe('BlockImage', () => {
    it('renders img with src', () => {
        const wrapper = mount(BlockImage, {
            props: { props: { url: 'https://example.com/x.png', alt: 'X' } },
        })
        expect(wrapper.find('img').attributes('src')).toBe('https://example.com/x.png')
        expect(wrapper.find('img').attributes('alt')).toBe('X')
    })
})
