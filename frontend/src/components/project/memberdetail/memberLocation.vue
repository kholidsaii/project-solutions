<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['memberData']);
const sites = ref<any[]>([]);

onMounted(async () => {
  try {
    const res = await api.get('/master-data/locations');
    sites.value = res.data || [];
  } catch (e) { console.error(e); }
});
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
       <div class="flex items-center gap-4">
          <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center"><i class="fas fa-map-marked-alt"></i></div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Assigned Work Sites</h3>
       </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="site in sites" :key="site.id" class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm hover:border-rose-300 transition-all group">
        <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 mb-4 group-hover:bg-rose-50 group-hover:text-rose-500 transition-colors">
          <i class="fas fa-building text-xl"></i>
        </div>
        <h4 class="text-xs font-black text-slate-800 uppercase mb-2">{{ site.name }}</h4>
        <p class="text-[10px] font-bold text-slate-400 uppercase leading-relaxed mb-6">{{ site.address }}</p>
        <button class="w-full py-3 rounded-xl bg-slate-50 text-[9px] font-black text-slate-500 uppercase hover:bg-rose-500 hover:text-white transition-all">
          <i class="fas fa-external-link-alt mr-1"></i> View on Maps
        </button>
      </div>
    </div>
  </div>
</template>