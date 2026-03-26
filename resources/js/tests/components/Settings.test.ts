import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        props: ['title'],
        template: '<span class="head-stub" />',
    },
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a><slot /></a>',
    },
}))

import Settings from '../../Pages/Dashboard/Settings.vue'

describe('Settings page', () => {
    const globalStubs = {
        AuthenticatedLayout: {
            template: '<div><slot name="header" /><slot /></div>',
        },
    }

    it('renders name, slug, and bio fields', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: 'Ana', slug: 'ana', bio: 'Hi', avatar_url: null } },
            global: { stubs: globalStubs },
        })
        expect(wrapper.find('input[name="name"]').exists()).toBe(true)
        expect(wrapper.find('input[name="slug"]').exists()).toBe(true)
        expect(wrapper.find('textarea[name="bio"]').exists()).toBe(true)
    })

    it('shows avatar preview when avatar_url is set', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: '', slug: '', bio: '', avatar_url: '/img/test.jpg' } },
            global: { stubs: globalStubs },
        })
        expect(wrapper.find('img').attributes('src')).toBe('/img/test.jpg')
    })

    it('shows remove avatar button when avatar exists', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: '', slug: '', bio: '', avatar_url: '/img/test.jpg' } },
            global: { stubs: globalStubs },
        })
        expect(wrapper.text()).toContain('Borrar avatar')
    })

    it('disables Restaurar todo when slug is number but matches form (no false pending changes)', () => {
        const wrapper = mount(Settings, {
            props: { site: { name: 'A', slug: 123, bio: '', avatar_url: null } },
            global: { stubs: globalStubs },
        })
        const restoreAll = wrapper.find('[data-action="restore-all"]')
        expect(restoreAll.exists()).toBe(true)
        expect(restoreAll.attributes('disabled')).toBeDefined()
    })
})
