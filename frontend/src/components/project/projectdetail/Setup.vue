<script setup lang="ts">
// Menerima data project dari parent (ProjectDetail.vue)
const props = defineProps(['project']);
</script>

<template>
  <div class="animate-in fade-in duration-500 bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm text-center">
    <i class="fas fa-cog text-4xl text-slate-300 mb-4"></i>
    <h2 class="text-lg font-black text-slate-700 uppercase">Halaman Setup</h2>
    <p class="text-xs font-bold text-slate-400 mt-2">Fitur ini sedang dalam tahap pengembangan.</p>
  </div>
</template>