<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['memberData']);
const workOrders = ref<any[]>([]);
const isLoading = ref(true);

const fetchMemberWO = async () => {
  try {
    // Mengambil WO yang assigned_to member ini
    const res = await api.get(`/finance/consolidated?pt_id=all`); 
    // Note: Anda perlu membuat endpoint khusus di Laravel untuk memfilter WO per user
    workOrders.value = res.data.work_orders || [];
  } catch (e) { console.error(e); }
  finally { isLoading.value = false; }
};

onMounted(fetchMemberWO);
</script>

<template>
  <div class="space-y-6 animate-in slide-in-from-right-4 duration-500">
    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg"><i class="fas fa-tasks text-sm"></i></div>
        <div>
          <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Personal Work Orders</h4>
          <p class="text-[8px] font-bold text-slate-400 uppercase">Tugas dan tanggung jawab aktif</p>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-12"><i class="fas fa-spinner fa-spin text-indigo-500 text-2xl"></i></div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="wo in workOrders" :key="wo.id" class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md transition-all group">
        <div class="flex justify-between items-start mb-3">
          <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest" :class="wo.status === 'Selesai' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ wo.status }}</span>
          <span class="text-[9px] font-bold text-slate-400">ID-{{ wo.id }}</span>
        </div>
        <h5 class="text-xs font-black text-slate-800 uppercase mb-2">{{ wo.title }}</h5>
        <p class="text-[10px] text-slate-500 line-clamp-2 uppercase leading-relaxed mb-4">{{ wo.description || 'No Description' }}</p>
        <div class="pt-4 border-t border-slate-50 flex justify-between items-center">
          <span class="text-[9px] font-black text-rose-500 uppercase"><i class="fas fa-flag mr-1"></i> {{ wo.priority }}</span>
        </div>
      </div>

      <div v-if="workOrders.length === 0" class="col-span-full py-20 text-center border-2 border-dashed rounded-3xl">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum ada tugas yang diberikan</p>
      </div>
    </div>
  </div>
</template>