import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockText from '../../../Components/Blocks/BlockText.vue'

describe('BlockText', () => {
    it('renders content', () => {
        const wrapper = mount(BlockText, {
            props: { props: { content: 'Hola', align: 'left' } },
        })
        expect(wrapper.text()).toContain('Hola')
    })
})
