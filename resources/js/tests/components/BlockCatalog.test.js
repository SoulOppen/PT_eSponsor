import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import BlockCatalog from '../../Components/Editor/BlockCatalog.vue'

const schemas = {
    text: { label: 'Texto', icon: 'text' },
    links: { label: 'Links', icon: 'link' },
    image: { label: 'Imagen', icon: 'image' },
}

describe('BlockCatalog', () => {
    it('renders one card per block type', () => {
        const wrapper = mount(BlockCatalog, { props: { schemas } })
        expect(wrapper.findAll('[data-block-type]')).toHaveLength(3)
    })

    it('emits select with block type on click', async () => {
        const wrapper = mount(BlockCatalog, { props: { schemas } })
        await wrapper.find('[data-block-type="text"]').trigger('click')
        expect(wrapper.emitted('select')[0][0]).toBe('text')
    })
})
