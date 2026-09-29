import api from './api'

export const shopService = {
  async getItems(category = null) {
    const params = category ? { category } : {}
    const res = await api.get('/shop/items', { params })
    return res.data
  },

  async getInventory() {
    const res = await api.get('/shop/inventory')
    return res.data
  },

  async purchase(itemId) {
    const res = await api.post(`/shop/${itemId}/purchase`)
    return res.data
  },

  async equip(itemId) {
    const res = await api.post(`/shop/${itemId}/equip`)
    return res.data
  },

  async unequip(slot) {
    const res = await api.post(`/shop/unequip/${slot}`)
    return res.data
  },

  async getEquipped() {
    const res = await api.get('/shop/equipped')
    return res.data
  }
}

export default shopService
