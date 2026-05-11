<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

const formatCompact = (val: number) => {
    if (val === 0) return '0';
    return new Intl.NumberFormat('id-ID', { notation: "compact", maximumFractionDigits: 1 }).format(val);
};

// ==========================================
// 1. STATE & DATA
// ==========================================
const dbBanks = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);
const dbTransactions = ref<any[]>([]); // Untuk menghitung saldo real-time

const activeBankEntity = ref('all'); 
const searchBank = ref('');

const showBankModal = ref(false);
const bankForm = ref({ id: null as number | null, pt_id: '' as string | null, bank_name: '', account_name: '', account_number: '', branch_office: '' });

const showMutasiModal = ref(false);
const selectedBankMutasi = ref<any>(null);

// ==========================================
// 2. KALKULASI SALDO & MUTASI (REAL-TIME)
// ==========================================
const enhancedBanks = computed(() => {
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
    data = data.filter(b => b.bank_name.toLowerCase().includes(q) || b.account_name.toLowerCase().includes(q) || b.account_number.includes(q));
  }

  // Hitung Saldo berdasarkan Transaksi yang berstatus "Selesai"
  return data.map(bank => {
    const mutasi = dbTransactions.value.filter(t => 
        t.status.toLowerCase() === 'selesai' && 
        (t.bank_from == bank.id || t.bank_to == bank.id)
    );

    let total_in = 0;
    let total_out = 0;

    const mutasiList = mutasi.map(t => {
        let is_in = false;
        let is_out = false;
        
        // Jika uang menuju bank ini = Uang Masuk (Debit)
        if (t.bank_to == bank.id) { is_in = true; total_in += parseFloat(t.amount); }
        // Jika uang keluar dari bank ini = Uang Keluar (Kredit)
        if (t.bank_from == bank.id) { is_out = true; total_out += parseFloat(t.amount); }

        return { ...t, is_in, is_out };
    });

    // Urutkan mutasi dari yang paling baru
    mutasiList.sort((a, b) => new Date(b.transaction_date).getTime() - new Date(a.transaction_date).getTime());

    return {
        ...bank,
        total_in,
        total_out,
        balance: total_in - total_out,
        mutasi: mutasiList,
        // Warna border dinamis (Visual Candy)
        borderColor: ['bg-cyan-400', 'bg-blue-600', 'bg-emerald-400', 'bg-indigo-500', 'bg-amber-400'][bank.id % 5]
    };
  });
});

// ==========================================
// 3. FUNGSI HELPER & API
// ==========================================
const fetchMasterData = async () => {
  try {
    const [resBank, resComp, resTrx] = await Promise.all([
      api.get('/finance/banks'), 
      api.get('/companies'),
      api.get('/finance/transactions')
    ]);
    dbBanks.value = resBank.data.data || resBank.data;
    dbCompanies.value = resComp.data.data || resComp.data;
    dbTransactions.value = resTrx.data.data || resTrx.data;
  } catch (error) { console.error(error); }
};

const getBankName = (bankId: any) => {
  if (!bankId) return 'Kas Manual / Eksternal';
  const b = dbBanks.value.find(x => x.id == bankId);
  return b ? `${b.bank_name} (${b.account_name})` : 'Unknown';
};

const openBankModal = (bank: any = null) => {
  if (bank) {
    bankForm.value = { ...bank, pt_id: bank.pt_id || '' };
  } else {
    const defaultPt = (activeBankEntity.value !== 'all' && activeBankEntity.value !== 'personal') ? activeBankEntity.value : '';
    bankForm.value = { id: null, pt_id: defaultPt as string, bank_name: '', account_name: '', account_number: '', branch_office: '' };
  }
  showBankModal.value = true;
};

const openMutasi = (bank: any) => {
  selectedBankMutasi.value = bank;
  showMutasiModal.value = true;
};

const handleSaveBank = async () => {
  if (!bankForm.value.bank_name || !bankForm.value.account_number || !bankForm.value.account_name) return alert('Nama Bank, Nomor Rekening, dan Atas Nama wajib diisi!');
  try {
    const payload: Record<string, any> = { ...bankForm.value };
    if (!payload.pt_id) payload.pt_id = null;
    if (payload.id) { await api.put(`/finance/banks/${payload.id}`, payload); alert("Rekening diperbarui!"); } 
    else { await api.post('/finance/banks', payload); alert("Rekening baru dibuat!"); }
    showBankModal.value = false; await fetchMasterData(); 
  } catch (error: any) { alert(error.response?.data?.error || "Terjadi kesalahan."); }
};

const handleDeleteBank = async (id: number) => {
  if (!confirm("Yakin ingin menghapus rekening ini?")) return;
  try { 
    await api.delete(`/finance/banks/${id}`); alert("Rekening dihapus!"); await fetchMasterData(); 
  } catch (error: any) { alert("Gagal menghapus. Rekening sedang digunakan di Transaksi/Akuntansi."); }
};

onMounted(fetchMasterData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 text-slate-800 font-sans">
    
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

    <div class="lg:col-span-9 flex flex-col gap-4 min-h-[600px]">
      
      <div class="bg-white border border-slate-200 rounded-lg p-2 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
        <div class="flex items-center gap-2 w-full px-2">
          <div class="bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold shadow-sm w-48 justify-center cursor-pointer hover:bg-blue-700 whitespace-nowrap">
            <i class="fas fa-university"></i> Banking Accounts
          </div>
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input v-model="searchBank" type="text" placeholder="Cari Rekening, Pemilik, atau Nama Bank..." class="w-full pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-md text-xs font-medium outline-none focus:ring-1 ring-blue-300 uppercase">
          </div>
          <button @click="openBankModal()" class="bg-blue-600 text-white px-6 py-2 rounded-md flex items-center gap-2 text-xs font-bold shadow-md hover:bg-blue-700 whitespace-nowrap">
            <i class="fas fa-plus"></i> Tambah Rek
          </button>
        </div>
      </div>

      <div class="flex-1 space-y-3 mt-2">
        <div v-if="enhancedBanks.length === 0" class="p-10 text-center text-slate-400 border-2 border-dashed rounded-xl">
           <i class="fas fa-folder-open text-4xl mb-3"></i>
           <p class="text-xs font-bold uppercase tracking-widest">Tidak ada rekening ditemukan</p>
        </div>

        <div v-for="bank in enhancedBanks" :key="bank.id" @click="openMutasi(bank)" class="bg-white border border-slate-200 rounded-lg flex relative overflow-hidden group shadow-sm hover:shadow-md transition-all cursor-pointer">
          
          <div class="w-3 shrink-0" :class="bank.borderColor"></div>
          
          <div class="p-4 flex flex-col md:flex-row items-center justify-between gap-4 w-full">
             
             <div class="flex items-center gap-5 w-full md:w-auto overflow-hidden">
                <div class="w-16 h-16 rounded-full border border-slate-200 flex shrink-0 items-center justify-center overflow-hidden bg-slate-50 text-indigo-500">
                   <i class="fas fa-university text-2xl"></i>
                </div>

                <div class="flex-1 min-w-0">
                   <div class="flex flex-wrap gap-2 mb-2">
                     <span v-if="bank.pt_id" class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-blue-700 uppercase tracking-widest"><i class="fas fa-building mr-1"></i> Corporate</span>
                     <span v-else class="px-3 py-1 rounded-full text-[10px] font-black text-slate-700 bg-yellow-300 uppercase tracking-widest"><i class="fas fa-user-tie mr-1"></i> Personal</span>
                     
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-rose-500 uppercase">{{ bank.bank_name }}</span>
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-green-500 uppercase">{{ bank.mutasi.length }} Transaksi</span>
                   </div>

                   <h4 class="text-base font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-blue-600 transition-colors">{{ bank.account_number }}</h4>
                   
                   <div class="text-[10px] font-bold text-slate-500 mt-1 leading-tight">
                     <p>A/N Rekening<span class="ml-2 mr-2">:</span><span class="text-slate-700 font-black">{{ bank.account_name }}</span></p>
                     <p>Cabang Bank<span class="ml-3 mr-2">:</span>{{ bank.branch_office || 'Kantor Pusat / Online' }}</p>
                     <p>Affiliasi<span class="ml-[34px] mr-2">:</span>{{ bank.pt_id ? dbCompanies.find(c => c.id === bank.pt_id)?.name : 'Internal / Pribadi' }}</p>
                   </div>
                </div>
             </div>

             <div class="shrink-0 flex items-center gap-6">
                <div class="flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                   <button @click.stop="openBankModal(bank)" class="w-8 h-8 rounded-lg bg-slate-100 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-edit text-xs"></i></button>
                   <button @click.stop="handleDeleteBank(bank.id)" class="w-8 h-8 rounded-lg bg-slate-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-trash text-xs"></i></button>
                </div>

                <div class="w-24 h-24 rounded-full flex flex-col items-center justify-center border-[5px] border-white ring-[3px]" :class="bank.balance >= 0 ? 'bg-green-100 ring-green-400' : 'bg-red-100 ring-red-400'">
                   <span class="font-black text-sm text-center px-1" :class="bank.balance >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCompact(bank.balance) }}</span>
                   <span class="text-[8px] font-bold uppercase mt-0.5" :class="bank.balance >= 0 ? 'text-green-600' : 'text-red-600'">Saldo Rek</span>
                </div>
             </div>

          </div>
        </div>

      </div>
    </div>


    <div v-if="showMutasiModal && selectedBankMutasi" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm" @click="showMutasiModal = false"></div>
      
      <div class="bg-white rounded-2xl w-full max-w-5xl h-[85vh] flex flex-col relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-lg"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
              <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter">{{ selectedBankMutasi.bank_name }} - {{ selectedBankMutasi.account_number }}</h3>
              <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">A.N: {{ selectedBankMutasi.account_name }} | Rekening Koran Mutasi</p>
            </div>
          </div>
          <button @click="showMutasiModal = false" class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center shadow-sm"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-6 bg-white shrink-0 grid grid-cols-1 md:grid-cols-3 gap-5 border-b border-slate-100">
           <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl shadow-inner">
              <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Uang Masuk (Inflow)</p>
              <h4 class="text-lg font-black text-emerald-600">{{ formatCurrency(selectedBankMutasi.total_in) }}</h4>
           </div>
           <div class="bg-rose-50 border border-rose-100 p-4 rounded-xl shadow-inner">
              <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">Uang Keluar (Outflow)</p>
              <h4 class="text-lg font-black text-rose-600">{{ formatCurrency(selectedBankMutasi.total_out) }}</h4>
           </div>
           <div class="bg-slate-800 border border-slate-900 p-4 rounded-xl shadow-lg text-white">
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Estimasi Saldo Tersedia</p>
              <h4 class="text-xl font-black" :class="selectedBankMutasi.balance < 0 ? 'text-rose-400' : 'text-green-400'">{{ formatCurrency(selectedBankMutasi.balance) }}</h4>
           </div>
        </div>

        <div class="flex-1 overflow-auto bg-slate-50 p-6 custom-scrollbar">
           <table class="w-full text-left bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
              <thead class="bg-slate-100 border-b border-slate-200">
                 <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                    <th class="p-4">Tanggal</th>
                    <th class="p-4">Keterangan / Ref ID</th>
                    <th class="p-4">Alur Transaksi (Lawan)</th>
                    <th class="p-4 text-right">Uang Masuk (CR)</th>
                    <th class="p-4 text-right">Uang Keluar (DB)</th>
                 </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                 <tr v-if="selectedBankMutasi.mutasi.length === 0">
                    <td colspan="5" class="py-12 text-center text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                       Belum ada histori transaksi selesai di rekening ini.
                    </td>
                 </tr>
                 <tr v-for="m in selectedBankMutasi.mutasi" :key="m.id" class="hover:bg-slate-50 transition-colors">
                    <td class="p-4 text-[10px] font-black text-slate-500">{{ m.transaction_date }}</td>
                    <td class="p-4">
                       <p class="text-xs font-black text-slate-800 uppercase leading-tight">{{ m.description }}</p>
                       <p class="text-[8px] font-bold text-slate-400 uppercase mt-1 tracking-widest"><i class="fas fa-barcode"></i> {{ m.transaction_number }}</p>
                    </td>
                    <td class="p-4">
                       <div class="flex items-center gap-2">
                          <span v-if="m.is_in" class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[9px] font-black uppercase"><i class="fas fa-arrow-down mr-1"></i> Dari: {{ getBankName(m.bank_from) }}</span>
                          <span v-if="m.is_out" class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-[9px] font-black uppercase"><i class="fas fa-arrow-up mr-1"></i> Ke: {{ getBankName(m.bank_to) }}</span>
                       </div>
                    </td>
                    <td class="p-4 text-right text-xs font-black text-emerald-600 bg-emerald-50/20">{{ m.is_in ? formatCurrency(parseFloat(m.amount)) : '-' }}</td>
                    <td class="p-4 text-right text-xs font-black text-rose-600 bg-rose-50/20">{{ m.is_out ? formatCurrency(parseFloat(m.amount)) : '-' }}</td>
                 </tr>
              </tbody>
           </table>
        </div>
      </div>
    </div>


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
.animate-in { animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>