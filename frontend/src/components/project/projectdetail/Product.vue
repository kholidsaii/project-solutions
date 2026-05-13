<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios';
const props = defineProps(['project']);
const emit = defineEmits(['refresh']);
const route = useRoute();

const prodForm = ref({ title: '', type: 'Link', version: '1.0.0', content: '' });

const handleSaveProduction = async () => {
  if (!prodForm.value.title || !prodForm.value.content) return;
  try {
    await api.post('/project-productions', { project_id: route.params.id, ...prodForm.value });
    prodForm.value = { title: '', type: 'Link', version: '1.0.0', content: '' };
    emit('refresh');
  } catch (e) { console.error(e); }
};

const handleDeleteProduction = async (id: number) => {
  try {
    await api.delete(`/project-productions/${id}`);
    emit('refresh');
  } catch (e) { console.error(e); }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center"><i class="fas fa-box-open text-xs"></i></div>
        <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">Submit Deliverable</h4>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="prodForm.title" type="text" placeholder="TITLE (E.G. STAGING URL)" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none focus:ring-2 ring-emerald-100 uppercase">
        <select v-model="prodForm.type" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
          <option value="Link">Web Link / URL</option>
          <option value="Credentials">Credentials / Login</option>
          <option value="File">File Path / Drive</option>
        </select>
        <input v-model="prodForm.version" type="text" placeholder="VERSION (E.G. 1.0.1)" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
      </div>
      <textarea v-model="prodForm.content" placeholder="CONTENT OR URL..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none focus:ring-2 ring-emerald-50 uppercase" rows="2"></textarea>
      <button @click="handleSaveProduction" class="w-full bg-emerald-600 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-[0.98] transition-all">Save Production Output</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="prod in project?.productions" :key="prod.id" class="bg-white border border-slate-100 p-5 rounded-3xl flex items-start gap-4 hover:shadow-md transition-all relative group">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner text-lg bg-blue-50 text-blue-600">
          <i :class="prod.type === 'Link' ? 'fas fa-link' : 'fas fa-key'"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h5 class="text-[11px] font-black text-slate-800 uppercase truncate">{{ prod.title }}</h5>
            <span class="text-[7px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase">v{{ prod.version }}</span>
          </div>
          <p class="text-[10px] font-mono text-blue-600 truncate mt-1 bg-blue-50/50 p-1 rounded">{{ prod.content }}</p>
        </div>
        <button @click="handleDeleteProduction(prod.id)" class="opacity-0 group-hover:opacity-100 absolute top-4 right-4 text-rose-300 hover:text-rose-500"><i class="fas fa-trash-alt"></i></button>
      </div>
    </div>
  </div>
</template>

