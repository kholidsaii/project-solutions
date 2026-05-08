<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../../api/axios';
const props = defineProps(['project', 'allCompanies']);
const emit = defineEmits(['refresh']);

const dbCOAs = ref<any[]>([]);
const activeAccEntity = ref('all');
const searchCOA = ref('');
const showCoaModal = ref(false);
const coaForm = ref({ id: null as number | null, pt_id: '', code: '', name: '', category: 'Asset' });

const filteredCOAs = computed(() => {
  let data = [...dbCOAs.value];
  if (searchCOA.value) data = data.filter(c => c.name.toLowerCase().includes(searchCOA.value.toLowerCase()));
  return data;
});

const fetchMasterFinance = async () => {
  try {
    const res = await api.get('/accounting/coas');
    dbCOAs.value = res.data.data || res.data;
  } catch (e) { console.error(e); }
};

const openCoaModal = (coa: any = null) => {
  coaForm.value = coa ? { ...coa, pt_id: coa.pt_id || '' } : { id: null, pt_id: '', code: '', name: '', category: 'Asset' };
  showCoaModal.value = true;
};

const handleSaveCOA = async () => {
  try {
    const payload: Record<string, any> = { ...coaForm.value };
    if (!payload.pt_id) payload.pt_id = null;
    if (payload.id) await api.put(`/accounting/coas/${payload.id}`, payload);
    else await api.post('/accounting/coas', payload);
    showCoaModal.value = false;
    await fetchMasterFinance();
  } catch (e) { console.error(e); }
};

const handleDeleteCOA = async (id: number) => {
  if (!confirm("Hapus akun COA?")) return;
  try {
    await api.delete(`/accounting/coas/${id}`);
    await fetchMasterFinance();
  } catch (e) { console.error(e); }
};

onMounted(fetchMasterFinance);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 relative">
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Business Entities</h3>
        <div class="space-y-2">
          <button @click="activeAccEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
            <span class="text-[11px] font-black uppercase">Consolidated (All)</span>
          </button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[500px]">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <input v-model="searchCOA" type="text" placeholder="Cari Kode atau Nama Akun..." class="w-full md:w-96 px-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none uppercase text-slate-700">
        <button @click="openCoaModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase shadow-xl hover:bg-indigo-700">Tambah COA</button>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden p-2">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase">
            <tr><th class="p-6">Code & Name</th><th class="p-6">Category</th><th class="p-6 text-right">Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="coa in filteredCOAs" :key="coa.id" class="border-t border-slate-50">
              <td class="p-6 font-black text-xs text-slate-700 uppercase">[{{ coa.code }}] {{ coa.name }}</td>
              <td class="p-6 text-[10px] font-black uppercase text-slate-500">{{ coa.category }}</td>
              <td class="p-6 text-right">
                <button @click="openCoaModal(coa)" class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 mr-2"><i class="fas fa-edit"></i></button>
                <button @click="handleDeleteCOA(coa.id)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500"><i class="fas fa-trash"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showCoaModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-[3rem] w-full max-w-lg shadow-2xl p-8 space-y-6">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-black text-slate-800 uppercase italic">{{ coaForm.id ? 'Edit' : 'Buat' }} Akun COA</h3>
          <button @click="showCoaModal = false" class="text-slate-400"><i class="fas fa-times text-xl"></i></button>
        </div>
        <input v-model="coaForm.code" type="text" placeholder="Kode Akun" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none">
        <input v-model="coaForm.name" type="text" placeholder="Nama Akun" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none">
        <select v-model="coaForm.category" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none">
          <option value="Asset">Asset (Harta)</option><option value="Liability">Liability (Kewajiban)</option><option value="Equity">Equity (Modal)</option><option value="Expense">Expense (Beban)</option><option value="Revenue">Revenue</option>
        </select>
        <button @click="handleSaveCOA" class="w-full bg-indigo-600 text-white py-5 rounded-2xl text-[10px] font-black uppercase shadow-xl hover:bg-indigo-700">Simpan Akun</button>
      </div>
    </div>
  </div>
</template>

