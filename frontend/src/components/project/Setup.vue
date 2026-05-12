<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios';

// ==========================================
// 1. STATE MANAGEMENT
// ==========================================
const setupTab = ref('project_assign');
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const isLoading = ref(false);

const openNavMenu = ref<string | null>(null);

// Pagination & Search State
const searchQueue = ref('');
const currentPage = ref(1);
const totalPages = ref(1);

const masterData = ref<any[]>([]);
const projectsList = ref<any[]>([]); 
const companiesList = ref<any[]>([]); 

// Form untuk Master Data & Assign
const setupForm = ref({ name: '' });
const showAssignModal = ref(false);
const selectedProject = ref<any>(null);
const selectedCompanyIds = ref<number[]>([]);

// ==========================================
// 2. FETCH DATA LOGIC (Backend Pagination)
// ==========================================
const fetchContent = async () => {
  isLoading.value = true;
  try {
    // Siapkan Parameter untuk Backend
    const params: any = { page: currentPage.value };
    if (searchQueue.value) params.search = searchQueue.value;

    if (setupTab.value === 'project_assign') {
      const [resProjects, resCompanies] = await Promise.all([
        api.get('/projects', { params }), // Fetch Projects dengan Pagination & Search
        api.get('/companies') // Ambil semua PT untuk Modal Dropdown Checkbox
      ]);
      
      // Deteksi struktur response pagination dari Laravel
      projectsList.value = resProjects.data.data || resProjects.data;
      totalPages.value = resProjects.data.last_page || 1;
      
      companiesList.value = resCompanies.data.data || resCompanies.data;
    } else {
      const res = await api.get(`/master-data/${setupTab.value}`, { params });
      
      masterData.value = res.data.data || res.data;
      totalPages.value = res.data.last_page || 1;
    }
  } catch (error) {
    console.error("Gagal memuat data:", error);
  } finally {
    isLoading.value = false;
  }
};

// Computed property untuk menentukan list apa yang dirender
const currentList = computed(() => {
  return setupTab.value === 'project_assign' ? projectsList.value : masterData.value;
});

// ==========================================
// 3. ACTIONS (SEARCH, PAGINATION, CRUD)
// ==========================================
const handleSearch = () => {
  currentPage.value = 1;
  fetchContent();
};

const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchContent(); } };
const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchContent(); } };
const goToPage = (page: number) => { currentPage.value = page; fetchContent(); };

const changeTab = (id: string) => {
  setupTab.value = id;
  isEditing.value = false;
  setupForm.value.name = '';
  searchQueue.value = '';
  currentPage.value = 1;
  fetchContent();
};

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

const openAssign = (project: any) => {
  selectedProject.value = project;
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

const formatTabName = (tab: string) => {
  if (tab === 'project_assign') return 'Project Assign';
  if (tab === 'tags_works') return 'Label Project';
  if (tab === 'tags_team') return 'Label Teamwork';
  if (tab === 'tags_docs') return 'Label Document';
  return tab.replace('_', ' ');
};

onMounted(() => fetchContent());
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm sticky top-8">
        <h4 class="text-[13px] font-bold text-center mb-3 pb-3 border-b border-gray-100 italic uppercase">Setup Navigation</h4>
        
        <div class="space-y-1">
          <button @click="changeTab('project_assign'); openNavMenu = null" 
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-[12px] font-medium"
            :class="setupTab === 'project_assign' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'">
            <div class="w-5 text-center shrink-0"><i class="fas fa-link text-gray-400" :class="setupTab === 'project_assign' ? 'text-blue-500' : ''"></i></div>
            <span>Project Assign</span>
          </button>

          <div class="border-t border-gray-100 my-2"></div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'data_project' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'data_project' ? null : 'data_project'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3"><div class="w-5 text-center shrink-0"><i class="fas fa-project-diagram text-gray-400"></i></div><span>Data Project</span></div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'data_project' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'data_project'" class="px-2 pb-2 space-y-1">
              <button @click="changeTab('status')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'status' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Status</button>
              <button @click="changeTab('priority')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'priority' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Priority</button>
              <button @click="changeTab('categories')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'categories' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Category</button>
              <button @click="changeTab('package')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'package' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Package Project</button>
              <button @click="changeTab('tags_works')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'tags_works' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Label Project</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'teamwork' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'teamwork' ? null : 'teamwork'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3"><div class="w-5 text-center shrink-0"><i class="fas fa-users text-gray-400"></i></div><span>Teamwork</span></div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'teamwork' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'teamwork'" class="px-2 pb-2 space-y-1">
              <button @click="changeTab('tags_team')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'tags_team' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Label Teamwork</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'document' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'document' ? null : 'document'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3"><div class="w-5 text-center shrink-0"><i class="fas fa-folder-open text-gray-400"></i></div><span>Document</span></div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'document' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'document'" class="px-2 pb-2 space-y-1">
              <button @click="changeTab('tags_docs')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="setupTab === 'tags_docs' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Label Document</button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-4 relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"><i class="fas fa-spinner fa-spin text-3xl text-[#4361EE]"></i></div>

      <div class="flex flex-col xl:flex-row gap-3 bg-white p-3 border border-gray-200 rounded-lg shadow-sm relative z-10">
        <button class="bg-[#4361EE] text-white px-5 py-2.5 rounded text-sm font-bold flex items-center gap-2 shrink-0 uppercase">
          <i class="fas fa-cog"></i> {{ formatTabName(setupTab) }}
        </button>
        <div class="flex-1 relative">
          <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input v-model="searchQueue" @keyup.enter="handleSearch" type="text" placeholder="Cari data master..." class="w-full bg-white border border-gray-300 rounded pl-10 pr-10 py-2.5 text-sm outline-none focus:border-[#4361EE] transition-colors">
          <button v-if="searchQueue" @click="searchQueue = ''; handleSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 hover:text-red-600 transition-colors">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <div v-if="setupTab !== 'project_assign'" class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex flex-col sm:flex-row gap-3 relative z-10">
        <div class="flex-1 relative">
          <input v-model="setupForm.name" type="text" @keyup.enter="handleSave" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-xs font-bold outline-none focus:border-[#4361EE] uppercase" placeholder="INPUT NAMA MASTER BARU...">
        </div>
        <button @click="handleSave" class="bg-[#4361EE] text-white px-6 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-blue-700 transition-all flex items-center justify-center gap-2 shadow-sm shrink-0">
          <i class="fas" :class="isEditing ? 'fa-save' : 'fa-plus'"></i> {{ isEditing ? 'Update' : 'Add Data' }}
        </button>
        <button v-if="isEditing" @click="isEditing = false; setupForm.name = ''" class="bg-gray-100 text-gray-500 px-4 py-2.5 rounded-lg text-xs font-bold uppercase hover:bg-gray-200 transition-all flex items-center justify-center gap-2 shrink-0">
          Batal
        </button>
      </div>

      <div class="bg-white border border-slate-100 rounded-2xl shadow-sm flex flex-col flex-1 overflow-hidden min-h-[500px]">
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-50">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest w-24 text-center">ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nama / Informasi</th>
                <th v-if="setupTab === 'project_assign'" class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">PT Terhubung</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-32">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="item in currentList" :key="item.id" class="hover:bg-slate-50/30 transition-colors group">
                <td class="px-6 py-4 text-center">
                  <span class="text-[10px] font-bold text-slate-400">#{{ item.id }}</span>
                </td>
                
                <td class="px-6 py-4">
                  <span class="text-[11px] font-black text-slate-700 uppercase">{{ item.name || item.project_title }}</span>
                </td>

                <td v-if="setupTab === 'project_assign'" class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="comp in item.companies" :key="comp.id" class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-1 rounded uppercase border border-indigo-100">
                      {{ comp.name }}
                    </span>
                    <span v-if="!item.companies?.length" class="text-[10px] font-bold text-slate-300 italic">Belum terhubung</span>
                  </div>
                </td>

                <td class="px-6 py-4 text-center">
                  <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <template v-if="setupTab === 'project_assign'">
                      <button @click="openAssign(item)" class="bg-[#4361EE] text-white text-[9px] font-black px-3 py-1.5 rounded hover:bg-blue-700 transition-all uppercase tracking-widest shadow-sm flex items-center gap-1">
                        <i class="fas fa-link"></i> Sync PT
                      </button>
                    </template>
                    <template v-else>
                      <button @click="openEdit(item)" class="w-7 h-7 rounded bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-edit text-[10px]"></i>
                      </button>
                      <button @click="handleDelete(item.id)" class="w-7 h-7 rounded bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                        <i class="fas fa-trash-alt text-[10px]"></i>
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
              <tr v-if="currentList.length === 0 && !isLoading">
                <td :colspan="setupTab === 'project_assign' ? 4 : 3" class="py-20 text-center">
                  <i class="fas fa-database text-3xl text-slate-200 mb-3"></i>
                  <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Data Master Kosong / Tidak Ditemukan</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="totalPages > 1" class="mt-auto p-4 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white relative z-10">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ totalPages }}</p>
          <div class="flex items-center gap-1.5">
            <button @click="prevPage" :disabled="currentPage === 1" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-all"><i class="fas fa-chevron-left text-[10px]"></i></button>
            <div class="flex items-center gap-1">
              <button v-for="page in totalPages" :key="page" @click="goToPage(page)" class="w-7 h-7 flex items-center justify-center rounded text-[10px] font-bold transition-all" :class="currentPage === page ? 'bg-[#4361EE] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'">{{ page }}</button>
            </div>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-all"><i class="fas fa-chevron-right text-[10px]"></i></button>
          </div>
        </div>
      </div>

    </div>

    <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Assign Companies to Project</h3>
          <button @click="showAssignModal = false" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center text-xs"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-6">
          <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Project Terpilih</label>
            <div class="bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-xs font-black text-[#4361EE] uppercase mt-2">
              {{ selectedProject?.project_title }}
            </div>
          </div>
          
          <div>
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Pilih PT / Perusahaan</label>
            <div class="grid grid-cols-1 gap-2 mt-3 max-h-60 overflow-y-auto custom-scrollbar pr-2">
              <label v-for="comp in companiesList" :key="comp.id" 
                class="flex items-center gap-4 p-4 rounded-xl border transition-all cursor-pointer"
                :class="selectedCompanyIds.includes(comp.id) ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-slate-100 hover:bg-slate-50'">
                <input type="checkbox" :value="comp.id" v-model="selectedCompanyIds" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ comp.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
          <button @click="showAssignModal = false" class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
          <button @click="handleSyncAssignment" class="bg-[#4361EE] text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-700 transition-colors">Simpan Assignment</button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>