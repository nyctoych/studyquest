<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    class="coin-icon"
  >
    <circle cx="12" cy="12" r="10" fill="url(#coinGrad)" stroke="#b45309" stroke-width="1.5" />
    <circle cx="12" cy="12" r="7.5" stroke="#fef08a" stroke-width="1" stroke-dasharray="2,2" />
    <!-- Embossed C or Star -->
    <path d="M 12 7 L 13.5 10.5 L 17 11 L 14.5 13.5 L 15 17 L 12 15.2 L 9 17 L 9.5 13.5 L 7 11 L 10.5 10.5 Z" fill="#92400e" opacity="0.8" />
    <defs>
      <radialGradient id="coinGrad" cx="35%" cy="30%" r="70%">
        <stop offset="0%" stop-color="#fde047" />
        <stop offset="60%" stop-color="#f59e0b" />
        <stop offset="100%" stop-color="#d97706" />
      </radialGradient>
    </defs>
  </svg>
</template>

<script setup>
defineProps({
  size: {
    type: [Number, String],
    default: 18
  }
})
</script>

<style scoped>
.coin-icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
  filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
}
</style>
