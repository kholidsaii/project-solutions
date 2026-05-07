<template>
  <div class="space-y-6 animate-in slide-in-from-right-4 duration-500 relative">
    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-blue-800 text-white flex items-center justify-center shadow-lg">
          <i class="fas fa-tasks text-sm"></i>
        </div>
        <div>
          <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Work Order Tasks</h4>
          <p class="text-[8px] font-bold text-slate-400 uppercase">Daftar Instruksi Kerja & Aktivitas</p>
        </div>
      </div>
      <button @click="openCreateWO" class="bg-blue-800 text-white px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-95 transition-all shadow-lg shadow-blue-200">
        <i class="fas fa-plus mr-1"></i> Buat Work Order
      </button>
    </div>

    <div v-if="isLoading" class="text-center py-12 bg-white rounded-2xl border border-slate-100">
      <i class="fas fa-circle-notch fa-spin text-3xl text-blue-600 mb-3"></i>
      <h5 class="text-xs font-bold text-slate-400 uppercase">Mengambil Data...</h5>
    </div>

    <div v-else-if="workOrders.length === 0" class="text-center py-12 bg-white rounded-2xl border border-slate-100 border-dashed">
      <i class="fas fa-clipboard-list text-4xl text-slate-200 mb-3"></i>
      <h5 class="text-xs font-bold text-slate-400 uppercase">Belum ada Work Order</h5>
      <p class="text-[10px] text-slate-400 mt-1">Data work order untuk project ini masih kosong.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="wo in workOrders" :key="wo.id" class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all relative group flex flex-col">
        
        <div class="flex justify-between items-start mb-3">
          <span class="text-[9px] font-black text-blue-800 uppercase tracking-wider bg-blue-50 px-2 py-1 rounded-md border border-blue-100">
            #WO-{{ wo.id }}
          </span>
          <span :class="statusClass(wo.status)" class="px-2.5 py-1 rounded-md text-[8px] font-black uppercase border">
            {{ wo.status }}
          </span>
        </div>

        <h5 class="text-sm font-bold text-slate-800 mb-2 leading-tight">{{ wo.title }}</h5>
        <p class="text-[10px] font-medium text-slate-500 line-clamp-3 mb-4 flex-grow">
          {{ wo.description || 'Tidak ada deskripsi detail untuk work order ini.' }}
        </p>

        <div class="mb-5 bg-slate-50 p-3 rounded-xl border border-slate-100">
          <div class="flex justify-between text-[9px] font-bold mb-1.5">
            <span class="text-slate-400 uppercase tracking-wider">Progress Aktivitas</span>
            <span class="text-blue-600">{{ calculateProgress(wo) }}%</span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
            <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-700 ease-out" :style="{ width: calculateProgress(wo) + '%' }"></div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-50 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300">
          <button @click="editWO(wo)" class="flex-1 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all text-[9px] font-bold uppercase border border-blue-100 hover:border-blue-600">
            <i class="fas fa-pencil-alt mr-1"></i> Edit
          </button>
          <button @click="deleteWO(wo.id)" class="py-1.5 px-3 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all text-[9px] font-bold uppercase border border-rose-100 hover:border-rose-500">
            <i class="fas fa-trash"></i>
          </button>
        </div>

      </div>
    </div>

    <div v-if="showWOModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h4 class="text-xs font-black uppercase text-blue-900 tracking-widest">
            {{ woForm.id ? 'Edit Work Order' : 'Buat Work Order Baru' }}
          </h4>
          <button @click="showWOModal = false" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center">
            <i class="fas fa-times text-xs"></i>
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Judul Task / Work Order</label>
            <input v-model="woForm.title" type="text" placeholder="Contoh: Setup Server Utama..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:border-blue-400 transition-colors">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-[9px] font-black text-slate-400 uppercase">Status</label>
              <select v-model="woForm.status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:border-blue-400 transition-colors cursor-pointer">
                <option value="Draft">Draft</option>
                <option value="Progress">On Progress</option>
                <option value="Done">Done</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[9px] font-black text-slate-400 uppercase">Prioritas</label>
              <select v-model="woForm.priority" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none focus:border-blue-400 transition-colors cursor-pointer">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Task Description</label>
            <textarea v-model="woForm.description" rows="4" placeholder="Jelaskan detail instruksi..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs font-medium text-slate-600 outline-none focus:border-blue-400 transition-colors"></textarea>
          </div>
        </div>

        <div class="p-5 bg-slate-50 border-t border-slate-100 flex gap-3">
          <button @click="saveWO" class="flex-1 bg-blue-700 hover:bg-blue-800 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-200 transition-all">
            {{ woForm.id ? 'Simpan Perubahan' : 'Simpan Work Order' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios'; // <-- Ganti sesuai path API axios kamu

// Tetap simpan props jika parent (ProjectDetail) sewaktu-waktu mengirim ulang
const props = defineProps({
  project: { type: Object, default: () => ({}) }
});
const emit = defineEmits(['refresh']);
const route = useRoute();

const showWOModal = ref(false);
const isLoading = ref(true);
const workOrders = ref<any[]>([]); // Array state independen anti-bug

const woForm = ref({ 
  id: null as number | null, 
  title: '', 
  description: '', 
  status: 'Draft',
  priority: 'Medium'
});

// FUNGSI INI AKAN MEMAKSA TARIK DATA LANGSUNG DARI DATABASE SAAT TAB DIBUKA
const fetchLocalWorkOrders = async () => {
  try {
    isLoading.value = true;
    const projectId = route.params.id;
    // Menggunakan API Project Controller show() yang terbukti berhasil mengambil work_orders
    const response = await api.get(`/projects/${projectId}`);
    workOrders.value = response.data.work_orders || [];
  } catch (error) {
    console.error("Gagal mengambil data work orders:", error);
  } finally {
    isLoading.value = false;
  }
};

// Lifecycle: Langsung tembak API sendiri begitu komponen siap
onMounted(() => {
  fetchLocalWorkOrders();
});

// Watcher untuk berjaga-jaga jika props project tiba-tiba update dari parent
watch(() => props.project?.work_orders, (newVal) => {
  if (newVal && newVal.length > 0) {
    workOrders.value = newVal;
  }
}, { deep: true });

const statusClass = (status: string) => {
  const s = status?.toLowerCase() || '';
  if (s === 'done' || s === 'completed') return 'bg-emerald-50 text-emerald-600 border-emerald-100';
  if (s === 'progress' || s === 'on progress') return 'bg-blue-50 text-blue-600 border-blue-100';
  return 'bg-slate-100 text-slate-500 border-slate-200';
};

const calculateProgress = (wo: any) => {
  if (wo.status === 'Done' || wo.status === 'Completed') return 100;
  const relatedTasks = props.project?.tasks?.filter((t: any) => t.work_order_id === wo.id) || [];
  if (relatedTasks.length === 0) return 0;
  
  const completedTasks = relatedTasks.filter((t: any) => t.is_completed).length;
  return Math.round((completedTasks / relatedTasks.length) * 100);
};

const openCreateWO = () => {
  woForm.value = { id: null, title: '', description: '', status: 'Draft', priority: 'Medium' };
  showWOModal.value = true;
};

const editWO = (wo: any) => {
  woForm.value = { 
    id: wo.id, 
    title: wo.title, 
    description: wo.description || '', 
    status: wo.status || 'Draft',
    priority: wo.priority || 'Medium'
  };
  showWOModal.value = true;
};

const saveWO = async () => {
  if (!woForm.value.title) return alert("Judul Task tidak boleh kosong!");
  
  try {
    const projectId = route.params.id;
    if (!projectId) return alert("Gagal menyimpan: ID Project tidak ditemukan!");

    const payload = { ...woForm.value, project_id: projectId };
    
    if (woForm.value.id) {
      await api.put(`/work-orders/${woForm.value.id}`, payload);
    } else {
      await api.post('/work-orders', payload);
    }
    
    showWOModal.value = false;
    await fetchLocalWorkOrders(); // Refresh lokal di dalam tab ini
    emit('refresh'); // Beritahu parent (opsional)
  } catch (e) { 
    console.error(e);
    alert("Terjadi kesalahan sistem saat menyimpan data.");
  }
};

const deleteWO = async (id: number) => {
  if (!confirm('Hapus Work Order / Task ini? Data tidak dapat dikembalikan.')) return;
  try {
    await api.delete(`/work-orders/${id}`);
    await fetchLocalWorkOrders(); // Refresh lokal
    emit('refresh');
  } catch (e) { 
    console.error(e); 
  }
};
</script>