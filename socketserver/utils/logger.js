function log(message) {
  const ts = new Date().toISOString().replace('T', ' ').substring(0, 19)
  console.log(`[${ts}] ${message}`)
}

module.exports = { log }
