import fs from 'fs'
import path from 'path'

function copyDir(src, dest) {
  if (!fs.existsSync(src)) return
  if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true })
  const entries = fs.readdirSync(src, { withFileTypes: true })
  for (const entry of entries) {
    const srcPath = path.join(src, entry.name)
    const destPath = path.join(dest, entry.name)
    if (entry.isDirectory()) {
      copyDir(srcPath, destPath)
    } else {
      if (!fs.existsSync(destPath) || fs.statSync(srcPath).mtimeMs > fs.statSync(destPath).mtimeMs) {
        fs.copyFileSync(srcPath, destPath)
      }
    }
  }
}

const folders = [
  'dotto',
  'bosses',
  'adventure',
  'game',
  'CHARACTERS',
  'BOSS',
  'ATTACK EFFECTS',
  'SPLASH EFFECTS',
  'POTIONS',
  'MUSIC',
  'PROFILE-AVATAR',
  'FINALBOSS BACKSTORY'
]

for (const folder of folders) {
  const rootDir = path.resolve('../assets', folder)
  const publicDir = path.resolve('public/assets', folder)

  // Sync root assets -> frontend public
  if (fs.existsSync(rootDir)) {
    copyDir(rootDir, publicDir)
  }
  // Sync frontend public -> root assets
  if (fs.existsSync(publicDir)) {
    copyDir(publicDir, rootDir)
    try {
      copyDir(publicDir, path.join('C:/xampp/htdocs/assets', folder))
    } catch (err) {}
  }
}

const fontsDir = path.resolve('public/fonts')
if (fs.existsSync(fontsDir)) {
  copyDir(fontsDir, path.resolve('../fonts'))
}

console.log('[Assets] Comprehensive asset synchronization complete.')
