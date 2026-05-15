<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['memberData']);
const activities = ref<any[]>([]);

const fetchActivities = async () => {
  try {
    // Fetch data project-tasks (activities) milik user ini
    const res = await api.get('/finance/transactions'); 
    // Disini idealnya filter backend: /project-tasks?user_id=...
    activities.value = res.data || [];
  } catch (e) { console.error(e); }
};

onMounted(fetchActivities);
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8 animate-in fade-in duration-700">
    <div v-for="act in activities" :key="act.id" class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm">
      <div class="p-6 flex items-center justify-between border-b border-slate-50">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xs uppercase">{{ memberData.name.substring(0,2) }}</div>
          <div>
            <h4 class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ act.task_name || 'Activity Posted' }}</h4>
            <p class="text-[9px] font-bold text-slate-400 uppercase">{{ act.created_at }} • Project ID: {{ act.project_id }}</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <p class="text-xs font-medium text-slate-600 leading-relaxed uppercase mb-4">{{ act.description || 'Melakukan update dokumentasi lapangan.' }}</p>
        <div v-if="act.document" class="rounded-2xl overflow-hidden border border-slate-100 mb-4 bg-slate-50 aspect-video flex items-center justify-center">
            <i class="fas fa-file-alt text-4xl text-slate-200"></i>
        </div>
      </div>
    </div>

    <div v-if="activities.length === 0" class="text-center py-20">
       <i class="fas fa-history text-4xl text-slate-100 mb-4"></i>
       <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum ada riwayat aktivitas</p>
    </div>
  </div>
</template>