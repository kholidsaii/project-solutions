<script setup lang="ts">
import { ref, watch } from 'vue'; // Tambahkan ref dan watch
import { useRoute } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['project', 'masterData', 'allCompanies']);
const emit = defineEmits(['refresh']);
const route = useRoute();

// Buat state khusus untuk menampung array ID PT yang dipilih
const selectedCompanyIds = ref<number[]>([]);

// Pantau (watch) perubahan data dari props.project. 
// Jika data project di-load, masukkan ID PT-nya ke dalam checkbox
watch(() => props.project, (newVal) => {
  if (newVal && newVal.companies) {
    selectedCompanyIds.value = newVal.companies.map((c: any) => c.id);
  }
}, { immediate: true, deep: true });

// Fungsi update khusus untuk detail project (Nama, tanggal, dll)
const updateDetail = async () => {
  try {
    await api.put(`/projects/detail/${route.params.id}`, props.project);
    emit('refresh');
  } catch (e) {
    console.error("Gagal update detail", e);
  }
};

// Fungsi BARU khusus untuk mengupdate relasi banyak PT (Many-to-Many)
const syncCompanies = async () => {
  try {
    // Kita panggil endpoint sync-companies yang sudah kamu buat di backend
    await api.post(`/projects/${route.params.id}/sync-companies`, {
      company_ids: selectedCompanyIds.value
    });
    emit('refresh'); // Refresh agar header di ProjectDetail.vue ikut berubah
  } catch (e) {
    console.error("Gagal sync PT", e);
    alert("Gagal memperbarui relasi PT.");
  }
};
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 animate-in fade-in duration-500">
    <div class="lg:col-span-2 space-y-10">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Identity</h4>
          <div class="space-y-3 pl-4">
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-slate-400 uppercase">Project Name</span>
              <input v-model="project.project_title" @change="updateDetail" class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] font-bold text-slate-400 uppercase mb-2">Affiliated PT (Owner)</span>
                
                <div class="grid grid-cols-1 gap-2 max-h-32 overflow-y-auto custom-scrollbar p-2 border border-slate-100 rounded-lg bg-slate-50/50">
                    <label v-for="cp in allCompanies" :key="cp.id" class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" :value="cp.id" v-model="selectedCompanyIds" @change="syncCompanies" 
                            class="w-3.5 h-3.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <span class="text-[10px] font-bold text-slate-600 uppercase group-hover:text-blue-600 transition-colors">
                        {{ cp.name }}
                    </span>
                    </label>
                    
                    <div v-if="allCompanies?.length === 0" class="text-[9px] text-slate-400 italic px-2">
                    No Organization Available
                    </div>
                </div>
            </div>
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-slate-400 uppercase">Customer / Client</span>
              <input v-model="project.client_name" @change="updateDetail" class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
            </div>
          </div>
        </div>

        <div>
          <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Timeline & Value</h4>
          <div class="space-y-3 pl-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Start Date</span>
                <input type="date" v-model="project.start_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
              </div>
              <div class="flex flex-col">
                <span class="text-[9px] font-bold text-slate-400 uppercase">Finish Date</span>
                <input type="date" v-model="project.finish_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
              </div>
            </div>
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-slate-400 uppercase">Contract Value</span>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] font-black text-slate-400">IDR</span>
                <input type="number" v-model="project.contract_value" @change="updateDetail" class="text-[11px] font-black text-emerald-600 outline-none bg-transparent border-b border-slate-100 w-full">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div>
        <h4 class="text-[11px] font-black text-blue-800 mb-3 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Description</h4>
        <textarea v-model="project.description" @change="updateDetail" rows="3" class="w-full pl-4 text-[11px] font-medium text-slate-600 bg-transparent border-none focus:ring-0 resize-none uppercase" placeholder="ADD DESCRIPTION..."></textarea>
      </div>
    </div>

    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 space-y-6">
      <h4 class="text-[11px] font-black text-blue-900 uppercase tracking-tighter text-center mb-4">Work Configuration</h4>
      <div class="space-y-4">
        <div class="flex flex-col">
          <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Work Category</label>
          <select v-model="project.category_id" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
            <option v-for="cat in masterData?.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="flex flex-col">
          <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Current Status</label>
          <select v-model="project.status" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
            <option v-for="s in masterData?.status" :key="s.id" :value="s.name">{{ s.name }}</option>
          </select>
        </div>
        <div class="flex flex-col">
          <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Priority Level</label>
          <select v-model="project.priority" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-rose-600 uppercase outline-none focus:ring-2 ring-rose-100">
            <option v-for="p in masterData?.priority" :key="p.id" :value="p.name">{{ p.name }}</option>
          </select>
        </div>
        <div class="flex flex-col">
          <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Contract Package</label>
          <select v-model="project.package" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-slate-700 uppercase outline-none">
            <option v-for="pkg in masterData?.package" :key="pkg.id" :value="pkg.name">{{ pkg.name }}</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
</style>

