<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import api from '../../api/axios';

// --- STATE ---
const setupTab = ref('categories');
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const selectedFile = ref<File | null>(null);

const setupForm = ref({ 
  name: '', 
  icon: 'fas fa-folder',
  client_name: '',
  start_date: '',
  finish_date: '',
  package: '',
  status: 'Planning',
  priority: 'Medium',
  color_code: '#3b82f6'
});

// PERUBAHAN 1: Tambahkan state penampung untuk tags baru
const allMasterData = ref({
  categories: [] as any[],
  status: [] as any[],
  priority: [] as any[],
  package: [] as any[],
  color: [] as any[],
  tags_works: [] as any[],
  tags_team: [] as any[],
  tags_docs: [] as any[],
  project_companies: [] as any[] // Disediakan jika nanti ingin fetch data pivot
});

// --- COMPUTED & WATCHERS ---
const filteredMasterData = computed(() => {
  const key = setupTab.value as keyof typeof allMasterData.value;
  return allMasterData.value[key] || [];
});

watch(setupTab, () => {
  cancelEdit();
});

// --- METHODS ---
const onFileChange = (e: any) => {
  selectedFile.value = e.target.files[0];
};

const handleEditMaster = (item: any) => {
  isEditing.value = true;
  editingId.value = item.id;
  
  setupForm.value.name = item.name;
  
  if (setupTab.value === 'categories') {
    setupForm.value.client_name = item.client_name || '-';
    setupForm.value.start_date = item.start_date || '';
    setupForm.value.finish_date = item.finish_date || '';
    setupForm.value.package = item.package || '-';
    setupForm.value.icon = item.icon || 'fas fa-folder';
  } else if (setupTab.value === 'color') {
    setupForm.value.color_code = item.color_code || '#cbd5e1';
  }
};

const cancelEdit = () => {
  isEditing.value = false;
  editingId.value = null;
  selectedFile.value = null;
  
  setupForm.value = { 
    name: '', icon: 'fas fa-folder', client_name: '-', start_date: '', finish_date: '', package: '-', status: 'Planning', priority: 'Medium', color_code: '#3b82f6' 
  };
};

const getImageUrl = (path: string | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:') || path.startsWith('http')) return path;

  const apiUrl = import.meta.env.VITE_API_URL || ''; 
  const baseUrl = apiUrl.replace('/api', '');
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

// --- API ACTIONS ---
// PERUBAHAN 2: Fetch data untuk tags_works, tags_team, dan tags_docs
const fetchMasterData = async () => {
  try {
    const [resCat, resStatus, resPriority, resPackage, resColor, resTagsWorks, resTagsTeam, resTagsDocs] = await Promise.all([
      api.get('/work-categories'),
      api.get('/master-data/status'),
      api.get('/master-data/priority'),
      api.get('/master-data/package'),
      api.get('/master-data/color'),
      // Endpoint ini otomatis aktif karena di Laravel getTableName() sudah kita atur
      api.get('/master-data/tags_works'),
      api.get('/master-data/tags_team'),
      api.get('/master-data/tags_docs')
    ]);

    allMasterData.value.categories = resCat.data;
    allMasterData.value.status = resStatus.data;
    allMasterData.value.priority = resPriority.data;
    allMasterData.value.package = resPackage.data;
    allMasterData.value.color = resColor.data;
    allMasterData.value.tags_works = resTagsWorks.data;
    allMasterData.value.tags_team = resTagsTeam.data;
    allMasterData.value.tags_docs = resTagsDocs.data;
  } catch (e) {
    console.error("Gagal sinkronisasi data master", e);
  }
};

const handleSaveMaster = async () => {
  // Cegah save jika sedang berada di tab project_companies (karena UI-nya berbeda/butuh multi-select)
  if (setupTab.value === 'project_companies') return;

  if (!setupForm.value.name) return alert("Nama wajib diisi!");

  const formData = new FormData();
  formData.append('name', setupForm.value.name);
  if (setupTab.value === 'color') formData.append('color_code', setupForm.value.color_code);
  
  formData.append('client_name', setupForm.value.client_name || '-');
  formData.append('start_date', setupForm.value.start_date || '');
  formData.append('finish_date', setupForm.value.finish_date || '');
  formData.append('package', setupForm.value.package || '-');

  if (setupTab.value === 'categories') {
    formData.append('icon', setupForm.value.icon || 'fas fa-folder');
    if (selectedFile.value) formData.append('image', selectedFile.value);
  }

  try {
    let url = `/master-data/${setupTab.value}`;
    if (isEditing.value && editingId.value) {
      url = `/master-data/${setupTab.value}/${editingId.value}`;
      formData.append('_method', 'PUT'); 
    }

    await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
    
    alert(`Data ${setupTab.value.replace('_', ' ')} berhasil ${isEditing.value ? 'diupdate' : 'disimpan'}!`);
    cancelEdit();
    fetchMasterData(); 
  } catch (e: any) {
    const msg = e.response?.data?.message || e.message;
    alert("Gagal memproses data: " + msg);
  }
};

const handleDeleteMaster = async (id: number) => {
  if (confirm('Yakin ingin menghapus data ini?')) {
    try {
      await api.delete(`/master-data/${setupTab.value}/${id}`);
      alert("Data berhasil dihapus!");
      fetchMasterData();
    } catch (e) {
      alert("Gagal menghapus data.");
    }
  }
};

// --- TAMBAHAN STATE UNTUK PROJECT ASSIGNMENT ---
const allCompanies = ref<any[]>([]);
const availableProjects = ref<any[]>([]);
const assignedPTList = ref<any[]>([]); // 1. TAMBAHKAN INI UNTUK DATA TABEL

const assignForm = ref({
  project_id: '',
  company_ids: [] as number[] // Array untuk menampung banyak ID PT
});

// Fungsi khusus untuk mengambil daftar PT dan Project
const fetchAssignData = async () => {
  try {
    const [resComp, resProj] = await Promise.all([
      api.get('/companies'),
      api.get('/projects')
    ]);
    allCompanies.value = resComp.data.data || resComp.data; // Handle pagination jika ada
    availableProjects.value = resProj.data.data || resProj.data;
  } catch (e) {
    console.error("Gagal memuat data relasi", e);
  }
};

// Pantau jika tab berpindah ke 'project_companies', panggil fungsinya
watch(setupTab, (newTab) => {
  if (newTab === 'project_companies') {
    fetchAssignData();
  } else {
    cancelEdit();
  }
});

// Fungsi untuk mengirim data ke Laravel
const handleAssignProject = async () => {
  if (!assignForm.value.project_id || assignForm.value.company_ids.length === 0) {
    return alert("Harap pilih 1 Project dan centang minimal 1 PT!");
  }
  
  try {
    // Memanggil endpoint sync yang sudah kita buat di Laravel sebelumnya
    await api.post(`/projects/${assignForm.value.project_id}/sync-companies`, {
      company_ids: assignForm.value.company_ids
    });
    
    alert("Project berhasil dihubungkan dengan PT terpilih!");
    
    // 2. PERUBAHAN: Jangan reset form, tapi langsung refresh data relasinya 
    // agar tabel di bawah otomatis ter-update dan user bisa melihat hasilnya
    fetchAssignedCompanies(); 
    
  } catch (e: any) {
    const msg = e.response?.data?.error || e.message;
    alert("Gagal menyimpan relasi: " + msg);
  }
};

// 1. Tambahkan state untuk efek loading
const isLoadingAssign = ref(false);

// 2. Fungsi yang dipanggil saat dropdown Project dipilih
const fetchAssignedCompanies = async () => {
  if (!assignForm.value.project_id) {
    assignedPTList.value = []; // Kosongkan tabel jika project dibatalkan
    return;
  }
  
  isLoadingAssign.value = true;
  try {
    // Memanggil API untuk mengambil detail project berdasarkan ID
    // CATATAN: Pastikan endpoint Laravel ini sudah mengembalikan relasi `with('companies')`
    const res = await api.get(`/projects/${assignForm.value.project_id}`);
    const projectData = res.data.data || res.data;
    
    // Jika backend mengembalikan relasi "companies", kita ambil ID-nya
    if (projectData.companies && projectData.companies.length > 0) {
      // a. Centang otomatis checkbox PT
      assignForm.value.company_ids = projectData.companies.map((c: any) => c.id);
      
      // b. 3. TAMBAHKAN INI: Masukkan data ke dalam state Tabel
      assignedPTList.value = projectData.companies; 
    } else {
      assignForm.value.company_ids = []; 
      assignedPTList.value = []; // Kosongkan tabel jika belum ada relasi
    }
  } catch (error) {
    console.error("Gagal mengambil data PT ter-assign:", error);
    assignForm.value.company_ids = [];
    assignedPTList.value = [];
  } finally {
    isLoadingAssign.value = false;
  }
};

onMounted(() => {
  fetchMasterData();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm sticky top-8">
        <div class="bg-slate-50/50 border-b border-slate-100 px-6 py-5">
          <span class="text-[11px] font-black text-indigo-900 uppercase tracking-[0.2em]">Master Setup</span>
        </div>
        
        <div class="p-4 space-y-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
          
          <!-- 1. MODULE: WORKS -->
          <div>
            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-3 tracking-widest pl-2 flex items-center gap-2">
              <i class="fas fa-tasks text-indigo-400"></i> Works Settings
            </h4>
            <div class="space-y-1">
              <button v-for="menu in [
                { id: 'categories', label: 'Project Titles' },
                { id: 'status', label: 'Status' },
                { id: 'priority', label: 'Priority' },
                { id: 'package', label: 'Package' },
                { id: 'color', label: 'Color Identity' },
                { id: 'tags_works', label: 'Works Tags' }
              ]" :key="menu.id" @click="setupTab = menu.id"
                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold rounded-2xl transition-all uppercase tracking-widest"
                :class="setupTab === menu.id ? 'bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100' : 'text-slate-500 hover:bg-slate-50 hover:text-indigo-500 border border-transparent'">
                — {{ menu.label }}
              </button>
            </div>
          </div>

          <!-- 2. MODULE: TEAMWORK -->
          <div class="pt-4 border-t border-slate-100">
            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-3 tracking-widest pl-2 flex items-center gap-2">
              <i class="fas fa-users-cog text-emerald-400"></i> Teamwork Settings
            </h4>
            <div class="space-y-1">
              <button v-for="menu in [
                { id: 'project_companies', label: 'Project Assign' },
                { id: 'tags_team', label: 'Team Tags' }
              ]" :key="menu.id" @click="setupTab = menu.id"
                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold rounded-2xl transition-all uppercase tracking-widest"
                :class="setupTab === menu.id ? 'bg-emerald-50 text-emerald-600 shadow-sm border border-emerald-100' : 'text-slate-500 hover:bg-slate-50 hover:text-emerald-500 border border-transparent'">
                — {{ menu.label }}
              </button>
            </div>
          </div>

          <!-- 3. MODULE: DOCUMENT -->
          <div class="pt-4 border-t border-slate-100">
            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-3 tracking-widest pl-2 flex items-center gap-2">
              <i class="fas fa-folder-open text-amber-400"></i> Document Settings
            </h4>
            <div class="space-y-1">
              <button v-for="menu in [
                { id: 'tags_docs', label: 'Document Tags' }
              ]" :key="menu.id" @click="setupTab = menu.id"
                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold rounded-2xl transition-all uppercase tracking-widest"
                :class="setupTab === menu.id ? 'bg-amber-50 text-amber-600 shadow-sm border border-amber-100' : 'text-slate-500 hover:bg-slate-50 hover:text-amber-500 border border-transparent'">
                — {{ menu.label }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6">
      
      <!-- Top Bar -->
      <div class="bg-white px-8 py-5 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center gap-4 text-indigo-900">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl shadow-inner text-indigo-600">
          <i class="fas fa-cogs"></i>
        </div>
        <div>
          <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Master Data Setup</h2>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Manage / {{ setupTab.replace('_', ' ') }}</p>
        </div>
      </div>

      <!-- Form Input Area -->
      <div class="bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm transition-all" :class="isEditing ? 'ring-2 ring-amber-200 bg-amber-50/10' : ''">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
          
          <!-- Default Name Field (Tampil untuk semua kecuali project_companies) -->
          <div v-if="setupTab !== 'project_companies'" :class="(setupTab === 'categories' || setupTab === 'color') ? 'lg:col-span-4' : 'lg:col-span-8'" class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">
              {{ setupTab === 'categories' ? 'Project Title' : setupTab.replace('_', ' ').toUpperCase() + ' NAME' }}
            </label>
            <input v-model="setupForm.name" type="text" 
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 shadow-inner uppercase"
              :placeholder="'ENTER ' + setupTab.replace('_', ' ') + '...'">
          </div>

          <!-- Extra Fields untuk Project Title / Categories -->
          <template v-if="setupTab === 'categories'">
            <div class="lg:col-span-4 space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Client Name</label>
              <input v-model="setupForm.client_name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none shadow-inner uppercase">
            </div>
            <div class="lg:col-span-4 space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Package Type</label>
              <select v-model="setupForm.package" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none shadow-inner uppercase appearance-none cursor-pointer">
                <option value="-">- No Package -</option>
                <option value="Bronze">Bronze</option>
                <option value="Silver">Silver</option>
                <option value="Gold">Gold</option>
              </select>
            </div>
            <div class="lg:col-span-6 space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Duration (Start - Finish)</label>
              <div class="flex gap-3">
                <input v-model="setupForm.start_date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-4 text-[10px] font-bold outline-none shadow-inner uppercase cursor-pointer">
                <input v-model="setupForm.finish_date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-4 text-[10px] font-bold outline-none shadow-inner uppercase cursor-pointer">
              </div>
            </div>
            <div class="lg:col-span-6 space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Icon & Logo Upload</label>
              <div class="flex gap-3">
                <input v-model="setupForm.icon" type="text" placeholder="fas fa-folder" class="flex-1 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-4 text-[10px] font-bold outline-none shadow-inner">
                <label class="w-12 h-12 bg-white border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center cursor-pointer hover:border-indigo-400 hover:text-indigo-500 transition-all flex-none">
                  <input type="file" @change="onFileChange" class="hidden" accept="image/*">
                  <i class="fas fa-cloud-upload-alt text-slate-400 text-sm"></i>
                </label>
              </div>
            </div>
          </template>

          <!-- Extra Fields untuk Color -->
          <template v-if="setupTab === 'color'">
            <div class="lg:col-span-4 space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Pick Color</label>
              <div class="flex gap-3 items-center">
                <input v-model="setupForm.color_code" type="color" 
                  class="w-12 h-12 border-none bg-transparent cursor-pointer rounded-xl">
                <input v-model="setupForm.color_code" type="text" 
                  class="flex-1 bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-[11px] font-bold outline-none uppercase shadow-inner"
                  placeholder="#000000">
              </div>
            </div>
          </template>

          <!-- UI Khusus untuk Project Companies (Many-to-Many Pivot) -->
          <template v-if="setupTab === 'project_companies'">
            <div class="lg:col-span-12 space-y-6">
              
              <!-- BOX ATAS: FORM ASSIGNMENT (Dropdown & Checkbox) -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-indigo-50/30 p-8 rounded-[2.5rem] border border-indigo-100/50">
                
                <!-- KIRI: Dropdown Pilih Project -->
                <div class="space-y-3">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-black">1</div>
                    <label class="text-[10px] font-black text-indigo-900 uppercase tracking-widest">Pilih Project</label>
                  </div>
                  <div class="relative">
                    <select v-model="assignForm.project_id" @change="fetchAssignedCompanies" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none cursor-pointer focus:ring-2 ring-indigo-200 shadow-sm transition-all">
                      <option value="" disabled>-- Klik untuk Pilih Project --</option>
                      <option v-for="pj in availableProjects" :key="pj.id" :value="pj.id">
                        {{ pj.project_title }}
                      </option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                  </div>
                </div>

                <!-- KANAN: Daftar Checkbox PT -->
                <div class="space-y-3">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-black">2</div>
                    <label class="text-[10px] font-black text-indigo-900 uppercase tracking-widest">Pilih / Ubah Entitas PT</label>
                  </div>
                  
                  <div class="bg-white border border-slate-200 rounded-2xl p-3 max-h-[160px] overflow-y-auto custom-scrollbar flex flex-col gap-1 shadow-sm relative">
                    <!-- Overlay Loading -->
                    <div v-if="isLoadingAssign" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl">
                      <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
                    </div>

                    <!-- Looping Checkbox -->
                    <label v-for="cp in allCompanies" :key="cp.id" class="flex items-center gap-4 cursor-pointer group p-3 hover:bg-slate-50 rounded-xl transition-all border border-transparent hover:border-slate-100">
                      <input type="checkbox" :value="cp.id" v-model="assignForm.company_ids" 
                        class="w-5 h-5 text-indigo-600 rounded-lg border-slate-300 focus:ring-indigo-500 cursor-pointer shadow-sm transition-all">
                      <div class="flex flex-col">
                        <span class="text-[11px] font-black text-slate-700 group-hover:text-indigo-600 uppercase transition-colors tracking-tight">{{ cp.name }}</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">{{ cp.legal_name || 'Mitra' }}</span>
                      </div>
                    </label>

                    <div v-if="allCompanies.length === 0" class="text-center py-4 opacity-50">
                      <i class="fas fa-building text-2xl mb-2 text-slate-400"></i>
                      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Belum ada data PT</p>
                    </div>
                  </div>
                </div>

                <!-- TOMBOL EKSEKUSI -->
                <div class="md:col-span-2 flex justify-end pt-5 border-t border-indigo-100/60 mt-2">
                  <button @click="handleAssignProject" :disabled="!assignForm.project_id || assignForm.company_ids.length === 0" 
                    class="bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-sync-alt"></i> Update Relasi PT
                  </button>
                </div>
              </div>

              <!-- BOX BAWAH: TABEL PT AKTIF (Muncul otomatis saat Project dipilih) -->
              <div v-if="assignForm.project_id" class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden animate-in fade-in duration-500 mt-6">
                <div class="bg-slate-50 border-b border-slate-100 px-8 py-5 flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fas fa-building"></i>
                  </div>
                  <div>
                    <h3 class="text-[11px] font-black text-slate-700 uppercase tracking-widest">Daftar PT Ter-Assign</h3>
                    <p class="text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wider">Entitas yang diizinkan mengerjakan project ini</p>
                  </div>
                </div>
                
                <div class="overflow-x-auto">
                  <table class="w-full text-left">
                    <thead class="border-b border-slate-100 bg-white">
                      <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="px-8 py-5 w-16">No</th>
                        <th class="px-6 py-5">Nama PT / Entitas</th>
                        <th class="px-6 py-5">Role Pivot</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <!-- Looping Data Tabel PT -->
                      <tr v-for="(pt, index) in assignedPTList" :key="pt.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-5 text-[11px] font-black text-slate-400">#{{ index + 1 }}</td>
                        <td class="px-6 py-5">
                          <div class="text-[11px] font-black text-indigo-600 uppercase">{{ pt.name }}</div>
                          <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">{{ pt.legal_name || '-' }}</div>
                        </td>
                        <td class="px-6 py-5">
                          <span class="px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-indigo-100">
                            {{ pt.pivot?.role || 'Partner' }}
                          </span>
                        </td>
                      </tr>
                      
                      <!-- Jika Data Tabel Kosong -->
                      <tr v-if="assignedPTList.length === 0 && !isLoadingAssign">
                        <td colspan="3" class="px-6 py-12 text-center">
                          <i class="fas fa-exclamation-circle text-4xl text-slate-200 mb-4"></i>
                          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum ada PT yang terhubung ke Project ini</p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </template>

          <!-- Action Buttons -->
          <div v-if="setupTab !== 'project_companies'" class="lg:col-span-4 flex gap-3 h-full">
            <button @click="handleSaveMaster" 
              class="w-full flex-1 text-white px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl transition-all active:scale-95"
              :class="isEditing ? 'bg-amber-500 shadow-amber-200' : 'bg-indigo-600 shadow-indigo-200 hover:bg-indigo-700'">
              <i class="fas" :class="isEditing ? 'fa-save' : 'fa-plus'"></i> {{ isEditing ? 'Update' : 'Save' }}
            </button>
            <button v-if="isEditing" @click="cancelEdit" class="px-6 py-4 rounded-2xl border border-rose-100 bg-rose-50 text-[10px] font-black text-rose-500 uppercase tracking-widest hover:bg-rose-100 transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>

      <!-- Data Table Area -->
      <div v-if="setupTab !== 'project_companies'" class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left min-w-[600px]">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="px-8 py-6 w-20">No</th>
                <th class="px-6 py-6">
                  {{ setupTab === 'categories' ? 'Project Details' : 'Name / Tag Value' }}
                </th>
                <th v-if="setupTab === 'categories'" class="px-6 py-6">Client</th>
                <th class="px-8 py-6 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="(item, index) in filteredMasterData" :key="item.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="px-8 py-5 text-[11px] font-black text-slate-400 group-hover:text-indigo-600 transition-colors">#{{ index + 1 }}</td>
                <td class="px-6 py-5">
                  <div class="flex items-center gap-4">
                    <!-- Color Indicator -->
                    <div v-if="setupTab === 'color'" 
                      :style="{ backgroundColor: item.color_code || '#cbd5e1' }" 
                      class="w-6 h-6 rounded-full shadow-sm border-2 border-white flex-none">
                    </div>
                    <!-- Image Indicator -->
                    <div v-if="setupTab === 'categories' && item.image_path" class="w-10 h-10 rounded-xl bg-slate-100 flex-none overflow-hidden border border-slate-200">
                      <img :src="getImageUrl(item.image_path)" class="w-full h-full object-cover">
                    </div>
                    <!-- Text Info -->
                    <div>
                      <div class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ item.name }}</div>
                      <div v-if="setupTab === 'categories'" class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ item.package }} Package</div>
                      <div v-if="setupTab === 'color'" class="text-[9px] text-slate-400 font-bold font-mono uppercase tracking-widest mt-0.5">
                        {{ item.color_code || '-' }}
                      </div>
                      <!-- Indicator untuk Tags -->
                      <div v-if="setupTab.includes('tags_')" class="mt-1">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-black uppercase tracking-widest">Tag</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td v-if="setupTab === 'categories'" class="px-6 py-5">
                  <div class="text-[10px] font-bold text-slate-500 uppercase">{{ item.client_name || '-' }}</div>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="handleEditMaster(item)" class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-edit text-[10px]"></i>
                    </button>
                    <button @click="handleDeleteMaster(item.id)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-trash-alt text-[10px]"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <!-- Empty State -->
              <tr v-if="filteredMasterData.length === 0">
                <td :colspan="setupTab === 'categories' ? 4 : 3" class="px-6 py-16 text-center">
                  <i class="fas fa-folder-open text-4xl text-slate-200 mb-3"></i>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No data available</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>