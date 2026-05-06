<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';

const dbBanks = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);

const activeBankEntity = ref('all'); 
const searchBank = ref('');

// Filter Data Rekening
const filteredBanks = computed(() => {
  let data = [...dbBanks.value];
  if (activeBankEntity.value !== 'all') {
    if (activeBankEntity.value === 'personal') {
      data = data.filter(b => b.pt_id === null || b.pt_id === '');
    } else {
      data = data.filter(b => b.pt_id === activeBankEntity.value);
    }
  }
  if (searchBank.value) {
    const q = searchBank.value.toLowerCase();
    data = data.filter(b => 
      b.bank_name.toLowerCase().includes(q) || 
      b.account_name.toLowerCase().includes(q) || 
      b.account_number.includes(q)
    );
  }
  return data;
});

const showBankModal = ref(false);
const bankForm = ref({ 
  id: null as number | null, 
  pt_id: '' as string | null, 
  bank_name: '', 
  account_name: '', 
  account_number: '', 
  branch_office: '' 
});

const fetchMasterData = async () => {
  try {
    const [resBank, resComp] = await Promise.all([
      api.get('/finance/banks'), 
      api.get('/companies')
    ]);
    dbBanks.value = resBank.data.data || resBank.data;
    dbCompanies.value = resComp.data.data || resComp.data;
  } catch (error) { 
    console.error(error); 
  }
};

const openBankModal = (bank: any = null) => {
  if (bank) {
    bankForm.value = { ...bank, pt_id: bank.pt_id || '' };
  } else {
    // Default form ke entitas yang sedang difilter jika bukan 'all'
    const defaultPt = (activeBankEntity.value !== 'all' && activeBankEntity.value !== 'personal') ? activeBankEntity.value : '';
    bankForm.value = { id: null, pt_id: defaultPt as string, bank_name: '', account_name: '', account_number: '', branch_office: '' };
  }
  showBankModal.value = true;
};

const handleSaveBank = async () => {
  if (!bankForm.value.bank_name || !bankForm.value.account_number || !bankForm.value.account_name) {
    return alert('Nama Bank, Nomor Rekening, dan Atas Nama wajib diisi!');
  }

  try {
    const payload: Record<string, any> = { ...bankForm.value };
    if (!payload.pt_id) payload.pt_id = null;

    if (payload.id) { 
      await api.put(`/finance/banks/${payload.id}`, payload); 
      alert("Rekening berhasil diperbarui!"); 
    } else { 
      await api.post('/finance/banks', payload); 
      alert("Rekening baru berhasil dibuat!"); 
    }
    
    showBankModal.value = false; 
    await fetchMasterData(); 
  } catch (error: any) { 
    alert(error.response?.data?.error || "Terjadi kesalahan saat menyimpan rekening."); 
  }
};

const handleDeleteBank = async (id: number) => {
  if (!confirm("Yakin ingin menghapus rekening ini? Pastikan rekening ini tidak sedang digunakan pada transaksi aktif.")) return;
  try { 
    await api.delete(`/finance/banks/${id}`); 
    alert("Rekening berhasil dihapus!");
    await fetchMasterData(); 
  } catch (error: any) { 
    alert(error.response?.data?.error || "Gagal menghapus rekening. Rekening mungkin sudah terikat dengan transaksi."); 
  }
};

onMounted(fetchMasterData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 sticky top-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Account Ownership</h3>
        <div class="space-y-2">
          
          <button @click="activeBankEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all text-left" :class="activeBankEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
            <i class="fas fa-layer-group text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">All Accounts</span>
          </button>
          
          <button @click="activeBankEntity = 'personal'" class="w-full flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all text-left" :class="activeBankEntity === 'personal' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
            <i class="fas fa-user-circle text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Personal / Staff</span>
          </button>
          
          <!-- Area List Perusahaan yang Bisa di-Scroll -->
          <div class="pt-2 border-t border-slate-100 max-h-[300px] overflow-y-auto custom-scrollbar pr-1 space-y-2">
            <button v-for="pt in dbCompanies" :key="pt.id" @click="activeBankEntity = pt.id" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left group" :class="activeBankEntity === pt.id ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
              <div class="w-6 h-6 rounded-lg bg-white border flex items-center justify-center transition-colors" :class="activeBankEntity === pt.id ? 'border-indigo-200' : 'border-slate-200 group-hover:border-indigo-200'">
                 <i class="fas fa-building text-[10px]" :class="activeBankEntity === pt.id ? 'text-indigo-500' : 'text-slate-300 group-hover:text-indigo-400'"></i> 
               </div>
              <span class="text-[10px] font-black uppercase truncate">{{ pt.name }}</span>
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- MAIN CONTENT TABLE (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
      
      <!-- Top Bar: Search & Add Button -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
          <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
          <input v-model="searchBank" type="text" placeholder="Cari Bank, Pemilik, atau No. Rek..." class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest text-slate-700">
        </div>

        <button @click="openBankModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:scale-[0.98] transition-all flex items-center justify-center gap-3">
          <i class="fas fa-plus-circle"></i> Tambah Rekening
        </button>
      </div>

      <!-- Table Area -->
      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 p-2">
          <table class="w-full text-left">
            <thead class="bg-slate-50 rounded-t-[2.5rem]">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="p-6 rounded-tl-[2rem]">Bank & Branch</th>
                <th class="p-6">Account Details</th>
                <th class="p-6">Affiliation</th>
                <th class="p-6 text-right rounded-tr-[2rem]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              
              <tr v-if="filteredBanks.length === 0">
                <td colspan="4" class="p-20 text-center text-slate-300">
                  <i class="fas fa-university text-5xl mb-4 opacity-50"></i>
                  <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada rekening ditemukan pada filter ini</p>
                </td>
              </tr>

              <tr v-for="bank in filteredBanks" :key="bank.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-6">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                      <i class="fas fa-university text-lg"></i>
                    </div>
                    <div>
                      <p class="text-xs font-black text-slate-800 uppercase group-hover:text-indigo-600 transition-colors">{{ bank.bank_name }}</p>
                      <p class="text-[9px] font-bold text-slate-400 uppercase mt-1"><i class="fas fa-map-marker-alt mr-1"></i> Cab. {{ bank.branch_office || 'Pusat/Online' }}</p>
                    </div>
                  </div>
                </td>
                <td class="p-6">
                  <p class="text-[13px] font-black text-slate-700 tracking-widest mb-1">{{ bank.account_number }}</p>
                  <p class="text-[10px] font-bold text-slate-500 uppercase flex items-center gap-1.5">
                     <span class="bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded text-[7px] border">A.N</span> {{ bank.account_name }}
                  </p>
                </td>
                <td class="p-6">
                  <!-- Visualisasi yang lebih jelas antara Personal vs Organisasi -->
                  <div v-if="bank.pt_id" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-xl border border-slate-200">
                    <div class="w-5 h-5 rounded-md bg-white border border-slate-200 flex items-center justify-center"><i class="fas fa-building text-[8px] text-slate-400"></i></div>
                    <span class="text-[9px] font-black text-slate-600 uppercase tracking-widest truncate max-w-[150px]" :title="dbCompanies.find(c => c.id === bank.pt_id)?.name">
                      {{ dbCompanies.find(c => c.id === bank.pt_id)?.name || 'Corporate' }}
                    </span>
                  </div>
                  <div v-else class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-xl border border-indigo-100">
                    <div class="w-5 h-5 rounded-md bg-white border border-indigo-100 flex items-center justify-center"><i class="fas fa-user-tie text-[8px] text-indigo-400"></i></div>
                    <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">Personal / Staff</span>
                  </div>
                </td>
                <td class="p-6 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openBankModal(bank)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                    <button @click="handleDeleteBank(bank.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-trash text-[10px]"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL FORM ADD/EDIT BANKING                -->
    <!-- ========================================== -->
    <div v-if="showBankModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showBankModal = false"></div>
      
      <div class="bg-white rounded-[3rem] w-full max-w-xl relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ bankForm.id ? 'Edit Rekening' : 'Tambah Rekening Baru' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Manajemen Rekening & E-Wallet</p>
          </div>
          <button @click="showBankModal = false" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all flex items-center justify-center shadow-sm">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-8 space-y-6">
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kepemilikan (Affiliation)</label>
            <div class="relative">
              <i class="fas fa-sitemap absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
              <select v-model="bankForm.pt_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl pl-12 pr-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="">-- PERSONAL / INDIVIDU --</option>
                <option v-for="pt in dbCompanies" :key="pt.id" :value="pt.id">Corporate: {{ pt.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Bank / E-Wallet</label>
              <input v-model="bankForm.bank_name" type="text" placeholder="Contoh: BCA, OVO, Dana..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300 shadow-inner">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kantor Cabang (Opsional)</label>
              <input v-model="bankForm.branch_office" type="text" placeholder="Contoh: KCP Jakarta..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300 shadow-inner">
            </div>
          </div>

          <div class="bg-indigo-50/50 p-6 rounded-[2rem] border border-indigo-100 shadow-inner space-y-5">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-indigo-500 uppercase tracking-widest ml-1">Nomor Rekening</label>
              <input v-model="bankForm.account_number" type="text" placeholder="Masukkan nomor tanpa spasi..." class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-sm font-black tracking-widest outline-none focus:ring-2 ring-indigo-200 text-slate-800">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-indigo-500 uppercase tracking-widest ml-1">Atas Nama (Sesuai Buku Tabungan)</label>
              <input v-model="bankForm.account_name" type="text" placeholder="Contoh: PT. Maju Bersama" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 text-slate-800">
            </div>
          </div>
        </div>

        <div class="p-8 border-t border-slate-100 flex justify-end gap-4 bg-slate-50/50 rounded-b-[3rem]">
          <button @click="showBankModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
          <button @click="handleSaveBank" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-2">
            <i class="fas fa-save"></i> {{ bankForm.id ? 'Simpan Perubahan' : 'Simpan Rekening' }}
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Menghilangkan icon panah bawaan browser untuk dropdown select */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>