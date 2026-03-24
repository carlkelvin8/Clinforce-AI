<template>
  <div class="animate-pulse">
    <div v-if="type === 'kpi'" class="bg-white p-5 rounded-2xl border border-gray-100 space-y-3">
      <div class="h-3 bg-gray-200 rounded w-1/2"></div>
      <div class="h-8 bg-gray-200 rounded w-1/3"></div>
      <div class="h-2 bg-gray-100 rounded w-2/3"></div>
    </div>
    <div v-else-if="type === 'row'" class="flex items-center gap-3 py-3 px-4 border-b border-gray-50">
      <div class="w-9 h-9 rounded-full bg-gray-200 shrink-0"></div>
      <div class="flex-1 space-y-2">
        <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        <div class="h-2 bg-gray-100 rounded w-1/2"></div>
      </div>
      <div class="h-5 w-16 bg-gray-200 rounded-full"></div>
    </div>
    <div v-else-if="type === 'card'" class="bg-white p-5 rounded-2xl border border-gray-100 space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-lg bg-gray-200 shrink-0"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-2/3"></div>
          <div class="h-3 bg-gray-100 rounded w-1/2"></div>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div class="h-8 bg-gray-100 rounded-lg"></div>
        <div class="h-8 bg-gray-100 rounded-lg"></div>
        <div class="h-8 bg-gray-100 rounded-lg"></div>
      </div>
    </div>
    <div v-else class="space-y-2">
      <div class="h-4 bg-gray-200 rounded w-3/4"></div>
      <div class="h-3 bg-gray-100 rounded w-1/2"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: { type: String, default: 'default' }, // kpi | row | card | default
})
</script>
