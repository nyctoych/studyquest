import fs from 'fs'
import path from 'path'

function copyDir(src, dest) {
  if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true })
  const entries = fs.readdirSync(src, { withFileTypes: true })
  for (const entry of entries) {
    const srcPath = path.join(src, entry.name)
    const destPath = path.join(dest, entry.name)
    if (entry.isDirectory()) {
      copyDir(srcPath, destPath)
    } else {
      fs.copyFileSync(srcPath, destPath)
    }
  }
}

const srcDir = path.resolve('public/assets/dotto')
if (fs.existsSync(srcDir)) {
  copyDir(srcDir, path.resolve('../assets/dotto'))
  try {
    copyDir(srcDir, 'C:/xampp/htdocs/assets/dotto')
  } catch (err) {
    // Non-blocking if permissions differ
  }
  console.log('[Assets] Successfully synced Dotto pixel emoji assets.')
}

const bossesDir = path.resolve('public/assets/bosses')
if (fs.existsSync(bossesDir)) {
  copyDir(bossesDir, path.resolve('../assets/bosses'))
  try {
    copyDir(bossesDir, 'C:/xampp/htdocs/assets/bosses')
  } catch (err) {
    // Non-blocking if permissions differ
  }
  console.log('[Assets] Successfully synced Chapter Boss assets.')
}

const adventureBgsDir = path.resolve('public/assets/adventure')
if (fs.existsSync(adventureBgsDir)) {
  copyDir(adventureBgsDir, path.resolve('../assets/adventure'))
  try {
    copyDir(adventureBgsDir, 'C:/xampp/htdocs/assets/adventure')
  } catch (err) {
    // Non-blocking if permissions differ
  }
  console.log('[Assets] Successfully synced Adventure chapter backgrounds.')
}

const fontsDir = path.resolve('public/fonts')
if (fs.existsSync(fontsDir)) {
  copyDir(fontsDir, path.resolve('../fonts'))
  console.log('[Assets] Successfully synced local font assets.')
}

const musicDir = path.resolve('public/assets/MUSIC')
if (fs.existsSync(musicDir)) {
  copyDir(musicDir, path.resolve('../assets/MUSIC'))
  try {
    copyDir(musicDir, 'C:/xampp/htdocs/assets/MUSIC')
  } catch (err) {
    // Non-blocking
  }
  console.log('[Assets] Successfully synced Music assets.')
}

