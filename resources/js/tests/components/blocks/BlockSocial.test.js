import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockSocial from '../../../Components/Blocks/BlockSocial.vue'

describe('BlockSocial', () => {
    it('renders one link per item', () => {
        const wrapper = mount(BlockSocial, {
            props: {
                props: {
                    links: [
                        { label: 'GH', url: 'https://github.com/a' },
                        { label: 'X', url: 'https://x.com/b' },
                    ],
                },
            },
        })
        expect(wrapper.findAll('a')).toHaveLength(2)
        expect(wrapper.find('a').attributes('href')).toBe('https://github.com/a')
    })
})
