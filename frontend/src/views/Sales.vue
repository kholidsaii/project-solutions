<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';

const leads = ref<any[]>([]);
const isLoading = ref(true);

const fetchLeads = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/sales/leads');
    leads.value = res.data;
  } catch (error) {
    console.error("Gagal ambil data sales:", error);
  } finally {
    setTimeout(() => { isLoading.value = false; }, 500);
  }
};

onMounted(fetchLeads);

const getStatusStyle = (status: string) => {
  switch (status.toUpperCase()) {
    case 'WON': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'OPEN': return 'bg-sky-50 text-sky-600 border-sky-100';
    case 'LOSS': return 'bg-rose-50 text-rose-600 border-rose-100';
    default: return 'bg-slate-50 text-slate-600 border-slate-100';
  }
};
</script>

<template>
  <div class="p-4 md:p-10 min-h-screen bg-slate-50">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 bg-fuchsia-500 rounded-[2rem] shadow-xl shadow-fuchsia-200 flex items-center justify-center text-white text-2xl -rotate-3">
          <i class="fas fa-briefcase"></i>
        </div>
        <div>
          <h1 class="text-3xl font-black text-indigo-950 italic uppercase leading-none tracking-tighter">Sales Leads</h1>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Marketing & Client Acquisition</p>
        </div>
      </div>
      
      <button class="bg-fuchsia-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-fuchsia-700 transition-all shadow-lg shadow-fuchsia-100">
        <i class="fas fa-user-plus mr-2"></i> Add New Lead
      </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
      <div v-for="stat in ['Total Leads', 'Potential', 'Converted', 'Rejected']" :key="stat" 
        class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm">
        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ stat }}</p>
        <p class="text-xl font-black text-indigo-950 mt-1">--</p>
      </div>
    </div>

    <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/50 border border-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50">
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Client Name</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Company</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
              <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xs uppercase">
                    {{ lead.client_name.substring(0, 2) }}
                  </div>
                  <span class="text-sm font-black text-slate-700 uppercase italic">{{ lead.client_name }}</span>
                </div>
              </td>
              <td class="px-8 py-6 text-xs font-bold text-slate-500 uppercase">{{ lead.company_name || '-' }}</td>
              <td class="px-8 py-6">
                <span :class="getStatusStyle(lead.status)" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase italic border">
                  {{ lead.status }}
                </span>
              </td>
              <td class="px-8 py-6 text-right">
                <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-300 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                  <i class="fas fa-ellipsis-h"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../style.css";
</style>