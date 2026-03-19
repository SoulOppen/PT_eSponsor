import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockLinks from '../../../Components/Blocks/BlockLinks.vue'

const props = {
    title: 'My Links',
    items: [
        { label: 'GitHub', url: 'https://github.com/user' },
        { label: 'Twitter', url: 'https://twitter.com/user' },
    ],
    color: '#000000',
    text_color: '#ffffff',
}

describe('BlockLinks', () => {
    it('renders one anchor per link item', () => {
        expect(mount(BlockLinks, { props: { props } }).findAll('a')).toHaveLength(2)
    })

    it('renders block title', () => {
        expect(mount(BlockLinks, { props: { props } }).text()).toContain('My Links')
    })

    it('links have correct href', () => {
        const wrapper = mount(BlockLinks, { props: { props } })
        expect(wrapper.find('a').attributes('href')).toBe('https://github.com/user')
    })
})
