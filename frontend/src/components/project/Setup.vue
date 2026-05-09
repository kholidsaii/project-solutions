<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios';

// ==========================================
// 1. STATE MANAGEMENT
// ==========================================
const setupTab = ref('tags_works'); // Tab default
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const isLoading = ref(false);

const masterData = ref<any[]>([]);
const projectsList = ref<any[]>([]); // Khusus tab Project Assign
const companiesList = ref<any[]>([]); // Daftar PT untuk Assignment
const searchQueue = ref('');

// Form untuk Master Data sederhana
const setupForm = ref({ name: '' });

// Form untuk Sync Project & PT
const showAssignModal = ref(false);
const selectedProject = ref<any>(null);
const selectedCompanyIds = ref<number[]>([]);

// Menu Sidebar (Lengkap: 3 Tags + Project Assign + Master Lainnya)
const menuItems = [
  { id: 'tags_works', label: 'Tags Data Project', icon: 'fas fa-project-diagram', color: 'text-indigo-500', bg: 'bg-indigo-50' },
  { id: 'tags_team', label: 'Tags Teamwork', icon: 'fas fa-users', color: 'text-emerald-500', bg: 'bg-emerald-50' },
  { id: 'tags_docs', label: 'Tags Documents', icon: 'fas fa-file-invoice', color: 'text-amber-500', bg: 'bg-amber-50' },
  { id: 'project_assign', label: 'Project Assign', icon: 'fas fa-link', color: 'text-rose-500', bg: 'bg-rose-50' }, // FITUR YANG DIKEMBALIKAN
  { id: 'categories', label: 'Kategori Project', icon: 'fas fa-folder', color: 'text-blue-500', bg: 'bg-blue-50' },
  { id: 'status', label: 'Work Status', icon: 'fas fa-check-circle', color: 'text-slate-500', bg: 'bg-slate-100' },
  { id: 'priority', label: 'Prioritas Project', icon: 'fas fa-flag', color: 'text-red-500', bg: 'bg-red-50' }
];

// ==========================================
// 2. FETCH DATA LOGIC
// ==========================================
const fetchContent = async () => {
  isLoading.value = true;
  try {
    if (setupTab.value === 'project_assign') {
      // Fetch khusus untuk Assignment Project & PT
      const [resProjects, resCompanies] = await Promise.all([
        api.get('/projects'),
        api.get('/companies')
      ]);
      projectsList.value = resProjects.data.data || resProjects.data;
      companiesList.value = resCompanies.data.data || resCompanies.data;
    } else {
      // Fetch master data biasa
      const res = await api.get(`/master-data/${setupTab.value}`);
      masterData.value = res.data.data || res.data;
    }
  } catch (error) {
    console.error("Gagal memuat data:", error);
  } finally {
    isLoading.value = false;
  }
};

const filteredData = computed(() => {
  const data = setupTab.value === 'project_assign' ? projectsList.value : masterData.value;
  if (!searchQueue.value) return data;
  return data.filter((item: any) => 
    (item.name || item.project_title || '').toLowerCase().includes(searchQueue.value.toLowerCase())
  );
});

// ==========================================
// 3. ACTIONS (SAVE, EDIT, DELETE, ASSIGN)
// ==========================================

// --- Master Data Actions ---
const handleSave = async () => {
  if (!setupForm.value.name) return alert('Nama harus diisi!');
  try {
    if (isEditing.value && editingId.value) {
      await api.put(`/master-data/${setupTab.value}/${editingId.value}`, setupForm.value);
    } else {
      await api.post(`/master-data/${setupTab.value}`, setupForm.value);
    }
    setupForm.value.name = '';
    isEditing.value = false;
    fetchContent();
  } catch (e) { alert('Gagal menyimpan data.'); }
};

const openEdit = (item: any) => {
  isEditing.value = true;
  editingId.value = item.id;
  setupForm.value.name = item.name;
};

const handleDelete = async (id: number) => {
  if (!confirm('Hapus data ini?')) return;
  try {
    await api.delete(`/master-data/${setupTab.value}/${id}`);
    fetchContent();
  } catch (e) { alert('Gagal menghapus.'); }
};

// --- Project Assign Actions ---
const openAssign = (project: any) => {
  selectedProject.value = project;
  // Map ID PT yang sudah terhubung (jika ada data 'companies' dari API)
  selectedCompanyIds.value = project.companies ? project.companies.map((c: any) => c.id) : [];
  showAssignModal.value = true;
};

const handleSyncAssignment = async () => {
  try {
    await api.post(`/projects/${selectedProject.value.id}/sync-companies`, {
      company_ids: selectedCompanyIds.value
    });
    showAssignModal.value = false;
    fetchContent();
    alert('Penghubung Project & PT berhasil diperbarui!');
  } catch (e) { alert('Gagal melakukan sinkronisasi.'); }
};

const changeTab = (id: string) => {
  setupTab.value = id;
  isEditing.value = false;
  setupForm.value.name = '';
  searchQueue.value = '';
  fetchContent();
};

onMounted(() => fetchContent());
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in duration-500 pb-20 mt-4">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm sticky top-8">
        <div class="bg-slate-50/50 border-b border-slate-100 px-8 py-6">
          <span class="text-[11px] font-black text-indigo-900 uppercase tracking-[0.2em]">Project Setup</span>
        </div>
        
        <div class="p-4 space-y-2 max-h-[75vh] overflow-y-auto custom-scrollbar">
          <button v-for="menu in menuItems" :key="menu.id" @click="changeTab(menu.id)"
            class="w-full flex items-center gap-4 px-5 py-4 text-[10px] font-black rounded-2xl transition-all uppercase tracking-widest group"
            :class="setupTab === menu.id ? 'bg-indigo-600 text-white shadow-xl' : 'text-slate-500 hover:bg-slate-50'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm transition-colors"
              :class="setupTab === menu.id ? 'bg-white/20 text-white' : menu.bg + ' ' + menu.color">
              <i :class="menu.icon"></i>
            </div>
            {{ menu.label }}
          </button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 space-y-6">
      
      <div class="bg-white px-8 py-6 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
          <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">{{ setupTab.replace('_', ' ') }} Management</h2>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Kelola data master untuk operasional project</p>
        </div>
        <div class="relative">
          <input v-model="searchQueue" type="text" placeholder="CARI..." class="bg-slate-50 border border-slate-100 rounded-full px-5 py-2.5 text-[10px] font-bold outline-none w-48 focus:w-64 transition-all uppercase">
          <i class="fas fa-search absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
        </div>
      </div>

      <div v-if="setupTab !== 'project_assign'" class="bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm">
        <div class="flex flex-col md:flex-row items-end gap-6">
          <div class="flex-1 space-y-2 w-full">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Input Nama Baru</label>
            <input v-model="setupForm.name" type="text" @keyup.enter="handleSave"
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase"
              placeholder="CONTOH: URGENT / KONTRAK / DLL">
          </div>
          <button @click="handleSave"
            class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl hover:bg-indigo-700 transition-all flex items-center gap-3">
            <i class="fas" :class="isEditing ? 'fa-save' : 'fa-plus-circle'"></i>
            {{ isEditing ? 'Update' : 'Tambah' }}
          </button>
        </div>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50/50 border-b border-slate-100">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="px-8 py-6 w-20 text-center">ID</th>
                <th class="px-6 py-6">Nama / Informasi</th>
                <th v-if="setupTab === 'project_assign'" class="px-6 py-6">PT Terhubung</th>
                <th class="px-8 py-6 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="item in filteredData" :key="item.id" class="hover:bg-slate-50/30 transition-colors group">
                <td class="px-8 py-5 text-[11px] font-black text-slate-300 text-center">#{{ item.id }}</td>
                
                <td class="px-6 py-5">
                  <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight">
                    {{ item.name || item.project_title }}
                  </span>
                </td>

                <td v-if="setupTab === 'project_assign'" class="px-6 py-5">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="comp in item.companies" :key="comp.id" class="text-[8px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded uppercase border border-indigo-100">
                      {{ comp.name }}
                    </span>
                    <span v-if="!item.companies?.length" class="text-[9px] font-bold text-slate-300 italic">Belum terhubung</span>
                  </div>
                </td>

                <td class="px-8 py-5 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <template v-if="setupTab === 'project_assign'">
                      <button @click="openAssign(item)" class="bg-rose-50 text-rose-600 text-[9px] font-black px-4 py-2 rounded-xl hover:bg-rose-600 hover:text-white transition-all uppercase tracking-widest">
                        <i class="fas fa-link mr-1"></i> Sync PT
                      </button>
                    </template>
                    <template v-else>
                      <button @click="openEdit(item)" class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center justify-center transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                      <button @click="handleDelete(item.id)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all"><i class="fas fa-trash-alt text-[10px]"></i></button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in zoom-in duration-200">
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Assign Companies to Project</h3>
          <button @click="showAssignModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-6">
          <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Project Terpilih</label>
            <div class="bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-xs font-black text-indigo-600 uppercase mt-2">
              {{ selectedProject?.project_title }}
            </div>
          </div>
          
          <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Pilih PT / Perusahaan</label>
            <div class="grid grid-cols-1 gap-2 mt-3 max-h-60 overflow-y-auto custom-scrollbar pr-2">
              <label v-for="comp in companiesList" :key="comp.id" 
                class="flex items-center gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                :class="selectedCompanyIds.includes(comp.id) ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-slate-100 hover:bg-slate-50'">
                <input type="checkbox" :value="comp.id" v-model="selectedCompanyIds" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-[10px] font-black text-slate-700 uppercase tracking-tight">{{ comp.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
          <button @click="showAssignModal = false" class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase">Batal</button>
          <button @click="handleSyncAssignment" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl">Simpan Assignment</button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>