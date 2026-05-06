<script setup lang="ts">
import { ref, onMounted } from 'vue'; // Hapus watch yang tidak terpakai
import api from '../../api/axios';

// ==========================================
// 1. MASTER LABELS (DATABASE)
// ==========================================
const dbLabels = ref<any[]>([]);
const activeSetupNav = ref('labels'); 
const showLabelModal = ref(false);

const labelForm = ref({ id: null as number | null, name: '', color: 'bg-slate-100 text-slate-600' });
const colorOptions = [
  { class: 'bg-rose-100 text-rose-600', name: 'Merah (Urgent/Expense)' },
  { class: 'bg-indigo-100 text-indigo-600', name: 'Ungu (Vendor/B2B)' },
  { class: 'bg-emerald-100 text-emerald-600', name: 'Hijau (Income/Approve)' },
  { class: 'bg-amber-100 text-amber-600', name: 'Kuning (Ops/Warning)' },
  { class: 'bg-slate-100 text-slate-600', name: 'Abu-abu (General)' },
];

const fetchMasterData = async () => {
  try {
    const res = await api.get('/master-data/labels');
    dbLabels.value = res.data.data || res.data;
  } catch (error) { console.error(error); }
};

const handleSaveLabel = async () => {
  try {
    const payload = { name: labelForm.value.name, color: labelForm.value.color };
    if (labelForm.value.id) { await api.put(`/master-data/labels/${labelForm.value.id}`, payload); } 
    else { await api.post('/master-data/labels', payload); }
    showLabelModal.value = false; await fetchMasterData(); 
  } catch (error) { alert("Terjadi kesalahan."); }
};

const handleDeleteLabel = async (id: number) => {
  if (!confirm("Yakin hapus label ini?")) return;
  try { await api.delete(`/master-data/labels/${id}`); await fetchMasterData(); } 
  catch (error) { console.error(error); }
};

// ==========================================
// 2. SYSTEM NAVIGATION CONFIG (LOCAL STORAGE)
// ==========================================
const defaultSysConfig = {
  trx_menus: [
    { id: 'pending', label: 'Pending / Diajukan', icon: 'fa-clock', color: 'text-amber-500', active: true },
    { id: 'approved', label: 'Approved / Sukses', icon: 'fa-check-double', color: 'text-emerald-500', active: true },
    { id: 'income', label: 'Uang Masuk (Income)', icon: 'fa-arrow-down', color: 'text-emerald-500', active: true },
    { id: 'expense', label: 'Uang Keluar (Expense)', icon: 'fa-arrow-up', color: 'text-rose-500', active: true },
  ],
  coa_categories: [
    { id: 'Asset', label: 'Harta (Assets)', icon: 'fa-wallet', color: 'text-emerald-500' },
    { id: 'Liability', label: 'Kewajiban (Liabilities)', icon: 'fa-hand-holding-usd', color: 'text-rose-500' },
    { id: 'Equity', label: 'Modal (Equity)', icon: 'fa-scale-balanced', color: 'text-indigo-500' },
    { id: 'Revenue', label: 'Pendapatan (Revenue)', icon: 'fa-arrow-trend-up', color: 'text-emerald-400' },
    { id: 'Expense', label: 'Beban (Expenses)', icon: 'fa-arrow-trend-down', color: 'text-rose-400' },
  ]
};

// Menarik data navigasi dari LocalStorage (atau pakai default jika kosong)
const sysConfig = ref<typeof defaultSysConfig>(JSON.parse(localStorage.getItem('kerjapro_finance_setup') || JSON.stringify(defaultSysConfig)));

const saveSysConfig = () => {
  localStorage.setItem('kerjapro_finance_setup', JSON.stringify(sysConfig.value));
  alert('Konfigurasi Navigasi Sistem berhasil diupdate!');
};

// Tambah Kategori COA Baru
const newCoaCat = ref({ id: '', label: '', icon: 'fa-folder', color: 'text-indigo-500' });
const addCoaCategory = () => {
  if (!newCoaCat.value.id || !newCoaCat.value.label) return alert("ID dan Label wajib diisi!");
  sysConfig.value.coa_categories.push({ ...newCoaCat.value });
  newCoaCat.value = { id: '', label: '', icon: 'fa-folder', color: 'text-indigo-500' };
  saveSysConfig();
};

const removeCoaCategory = (index: number) => {
  if (!confirm("Hapus navigasi kategori ini?")) return;
  sysConfig.value.coa_categories.splice(index, 1);
  saveSysConfig();
};

onMounted(fetchMasterData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    
    <!-- SIDEBAR NAVIGASI SETUP -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 sticky top-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">System Preferences</h3>
        <div class="space-y-2">
          <button @click="activeSetupNav = 'labels'" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left" :class="activeSetupNav === 'labels' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'"><i class="fas fa-tags text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Transaction Labels</span></button>
          <button @click="activeSetupNav = 'nav_trx'" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left" :class="activeSetupNav === 'nav_trx' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'"><i class="fas fa-list-ul text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Menu Transaksi</span></button>
          <button @click="activeSetupNav = 'nav_coa'" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left" :class="activeSetupNav === 'nav_coa' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'"><i class="fas fa-sitemap text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Kategori COA</span></button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-6 min-h-150">
      
      <!-- KONTEN: TRANSACTION LABELS -->
      <div v-if="activeSetupNav === 'labels'" class="flex-1 flex flex-col gap-6">
        <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex justify-between items-center">
          <div><h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Master Data: Labels</h3></div>
          <button @click="labelForm = { id: null, name: '', color: 'bg-slate-100 text-slate-600' }; showLabelModal = true" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase"><i class="fas fa-plus"></i> Buat Label</button>
        </div>
        <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden p-2">
          <table class="w-full text-left">
            <thead class="bg-slate-50 rounded-t-[2.5rem]">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="p-6 rounded-tl-4xl">Label Name</th>
                <th class="p-6">Visual Preview</th>
                <th class="p-6 text-right rounded-tr-4xl">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-if="dbLabels.length === 0"><td colspan="3" class="p-12 text-center text-slate-400 text-[10px]">Belum ada label.</td></tr>
              <tr v-for="lbl in dbLabels" :key="lbl.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-6 text-xs font-black text-slate-800 uppercase">{{ lbl.name }}</td>
                <td class="p-6"><span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border" :class="lbl.color"># {{ lbl.name }}</span></td>
                <td class="p-6 text-right"><div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100"><button @click="labelForm = {...lbl}; showLabelModal = true" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 flex items-center justify-center"><i class="fas fa-edit"></i></button><button @click="handleDeleteLabel(lbl.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 flex items-center justify-center"><i class="fas fa-trash"></i></button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- KONTEN: NAVIGASI MENU TRANSAKSI -->
      <div v-else-if="activeSetupNav === 'nav_trx'" class="flex-1 flex flex-col gap-6">
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2">Visibilitas Menu Transaksi</h3>
          <p class="text-[10px] font-bold text-slate-400 uppercase">Centang untuk menampilkan/menyembunyikan menu di Sidebar Tab Transaksi.</p>
          
          <div class="mt-8 space-y-3">
             <div v-for="(menu, idx) in sysConfig.trx_menus" :key="idx" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="flex items-center gap-4">
                   <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-sm" :class="menu.color"><i class="fas" :class="menu.icon"></i></div>
                   <div>
                     <p class="text-xs font-black text-slate-800 uppercase">{{ menu.label }}</p>
                     <p class="text-[8px] font-bold text-slate-400 uppercase">Filter ID: {{ menu.id }}</p>
                   </div>
                </div>
                <!-- Toggle Button Custom -->
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="menu.active" @change="saveSysConfig" class="sr-only peer">
                  <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
             </div>
             
             <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-center gap-3">
               <i class="fas fa-exclamation-triangle text-amber-500"></i>
               <p class="text-[9px] font-bold text-amber-700">Catatan: Status Database (Pending, Approved, Rejected) bersifat mutlak dan dikunci oleh sistem untuk menjaga integritas Akuntansi.</p>
             </div>
          </div>
        </div>
      </div>

      <!-- KONTEN: NAVIGASI KATEGORI COA -->
      <div v-else-if="activeSetupNav === 'nav_coa'" class="flex-1 flex flex-col gap-6">
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-2">Master Kategori COA</h3>
          <p class="text-[10px] font-bold text-slate-400 uppercase">Kategori ini akan muncul di Navigasi Accounting dan form input COA.</p>
          
          <div class="mt-8 space-y-3">
             <div v-for="(cat, idx) in sysConfig.coa_categories" :key="idx" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="flex items-center gap-4">
                   <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-sm" :class="cat.color"><i class="fas" :class="cat.icon"></i></div>
                   <div>
                     <p class="text-xs font-black text-slate-800 uppercase">{{ cat.label }}</p>
                     <p class="text-[8px] font-bold text-slate-400 uppercase">Value ID: {{ cat.id }}</p>
                   </div>
                </div>
                <button @click="removeCoaCategory(idx)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-trash text-[10px]"></i></button>
             </div>
          </div>

          <!-- Form Tambah Kategori Baru -->
          <div class="mt-8 p-6 bg-indigo-50/50 border border-indigo-100 rounded-4xl">
             <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-4">Tambah Kategori Baru</h4>
             <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                   <label class="text-[8px] font-bold uppercase text-slate-500 ml-1">Database ID</label>
                   <input v-model="newCoaCat.id" placeholder="E.G. INVENTORY" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-bold outline-none uppercase shadow-sm">
                </div>
                <div>
                   <label class="text-[8px] font-bold uppercase text-slate-500 ml-1">Display Label</label>
                   <input v-model="newCoaCat.label" placeholder="Persediaan Barang" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-bold outline-none uppercase shadow-sm">
                </div>
                <div>
                   <label class="text-[8px] font-bold uppercase text-slate-500 ml-1">FontAwesome Icon</label>
                   <input v-model="newCoaCat.icon" placeholder="fa-boxes" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-bold outline-none shadow-sm">
                </div>
                <button @click="addCoaCategory" class="bg-indigo-600 text-white py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100">Tambah</button>
             </div>
          </div>

        </div>
      </div>

    </div>

    <!-- MODAL LABEL -->
    <div v-if="showLabelModal" class="fixed inset-0 z-999 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLabelModal = false"></div>
      <div class="bg-white rounded-[3rem] w-full max-w-sm relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]"><div><h3 class="text-xl font-black text-slate-800 uppercase italic">{{ labelForm.id ? 'Edit Label' : 'Label Baru' }}</h3></div><button @click="showLabelModal = false" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times"></i></button></div>
        <div class="p-8 space-y-6">
          <div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Nama Label</label><input v-model="labelForm.name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase"></div>
          <div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Warna Label</label><select v-model="labelForm.color" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none"><option v-for="c in colorOptions" :key="c.class" :value="c.class">{{ c.name }}</option></select></div>
          <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center"><span class="px-4 py-2 rounded-lg text-[10px] font-black uppercase shadow-sm" :class="labelForm.color"># {{ labelForm.name || 'Preview Label' }}</span></div>
        </div>
        <div class="p-6 border-t border-slate-100 bg-slate-50/50 rounded-b-[3rem]"><button @click="handleSaveLabel" class="w-full bg-indigo-600 text-white py-4 rounded-2xl text-[10px] font-black uppercase">Simpan</button></div>
      </div>
    </div>

  </div>
</template>