<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router'; // Tambahkan ini
import api from '../api/axios';
import Works from '../components/Works.vue';

const route = useRoute();
const currentTab = ref('overview');
const isLoading = ref(true);
const isSaving = ref(false);
const showProjectModal = ref(false);

// State Data Dinamis
const categories = ref<any[]>([]);
const selectedCategoryId = ref<number | null>(null);
const workData = ref<any[]>([]);

// Form State
const newCategory = ref({ name: '', icon: 'fas fa-file-alt' });
const newProject = ref({
  category_id: null,
  client_name: '',
  project_title: '',
  contract_value: 0,
  deadline: '',
  description: ''
});

// Interface untuk Stats
interface StatItem {
  label: string;
  value: string;
  color: string;
  shadow: string;
  key: 'total' | 'finish' | 'progress' | 'planing' | 'pending';
}

const stats = ref<StatItem[]>([
  { label: 'Total', value: '0', color: 'bg-indigo-600', shadow: 'shadow-indigo-200', key: 'total' },
  { label: 'Finish', value: '0', color: 'bg-cyan-400', shadow: 'shadow-cyan-100', key: 'finish' },
  { label: 'Open', value: '0', color: 'bg-green-500', shadow: 'shadow-green-100', key: 'progress' },
  { label: 'Planing', value: '0', color: 'bg-amber-400', shadow: 'shadow-amber-100', key: 'planing' },
  { label: 'Pending', value: '0', color: 'bg-rose-500', shadow: 'shadow-rose-100', key: 'pending' },
]);

const barSeries = ref<any[]>([]);

// --- LOGIKA API ---

const fetchCategories = async () => {
  try {
    const res = await api.get('/work-categories');
    categories.value = res.data;
    
    // LOGIKA DEEP LINK: Cek jika ada kategori di URL (?cat=...)
    const catFromUrl = route.query.cat;
    if (catFromUrl) {
      selectCategory(Number(catFromUrl));
    } else if (categories.value.length > 0) {
      selectCategory(categories.value[0].id);
    }
  } catch (e) { console.error("Gagal load kategori", e); }
};

const selectCategory = async (id: number) => {
  selectedCategoryId.value = id;
  currentTab.value = 'data';
  isLoading.value = true;
  try {
    const res = await api.get(`/projects/category/${id}`);
    workData.value = res.data.map((item: any) => ({ ...item, tasks: item.tasks || [] }));
    
    // LOGIKA SCROLL: Jika ada ID project di URL (/projects/7)
    const projectIdFromUrl = route.params.id;
    if (projectIdFromUrl) {
      setTimeout(() => {
        const element = document.getElementById(`project-${projectIdFromUrl}`);
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'center' });
          element.classList.add('ring-4', 'ring-indigo-500', 'ring-offset-4');
          setTimeout(() => element.classList.remove('ring-4', 'ring-indigo-500', 'ring-offset-4'), 3000);
        }
      }, 500);
    }
  } catch (e) { workData.value = []; } finally { isLoading.value = false; }
};

const handleAddCategory = async () => {
  if (!newCategory.value.name) return;
  isSaving.value = true;
  try {
    await api.post('/work-categories', newCategory.value);
    newCategory.value = { name: '', icon: 'fas fa-file-alt' };
    await fetchCategories();
  } finally { isSaving.value = false; }
};

// INPUT PROJECT BARU
const handleSaveProject = async () => {
  if (!newProject.value.project_title || !selectedCategoryId.value) {
    alert("Lengkapi data project!");
    return;
  }
  isSaving.value = true;
  try {
    // Memastikan category_id terisi sesuai sidebar yang aktif jika belum dipilih
    newProject.value.category_id = selectedCategoryId.value as any;
    
    await api.post('/projects', newProject.value);
    alert('Project Berhasil Disimpan!');
    showProjectModal.value = false;
    // Reset Form
    newProject.value = { category_id: null, client_name: '', project_title: '', contract_value: 0, deadline: '', description: '' };
    selectCategory(selectedCategoryId.value as number); // Refresh data
    fetchStats(); // Update angka dashboard
  } catch (e) {
    alert("Gagal simpan project");
  } finally { isSaving.value = false; }
};

const addTask = async (projectId: number, event: any) => {
  const taskName = event.target.value;
  if (!taskName) return;
  try {
    const res = await api.post('/project-tasks', { project_id: projectId, task_name: taskName });
    const project = workData.value.find(p => p.id === projectId);
    if (project) {
      project.tasks.push({ id: res.data.id, task_name: taskName, is_completed: false });
    }
    event.target.value = '';
  } catch (e) { console.error("Gagal tambah task"); }
};

const toggleTaskStatus = async (task: any) => {
  try { await api.put(`/project-tasks/${task.id}/toggle`); } catch (e) { task.is_completed = !task.is_completed; }
};

const fetchStats = async () => {
  try {
    const res = await api.get('/works/stats');
    if (res?.data) {
      stats.value.forEach((s) => {
        const val = res.data.summary?.[s.key];
        if (val !== undefined) s.value = val.toLocaleString();
      });
      barSeries.value = [{ name: 'Project Growth', data: res.data.monthly || [] }];
    }
  } finally { isLoading.value = false; }
};

onMounted(() => {
  fetchCategories();
  fetchStats();
});
watch(() => route.params.id, (newId) => {
  if (newId && route.query.cat) selectCategory(Number(route.query.cat));
});
</script>

<template>
  <div class="min-h-screen bg-[#f0f2f5] pb-32 w-full overflow-x-hidden">
    
    <div class="bg-gradient-to-r from-indigo-900 via-blue-900 to-indigo-950 p-6 md:p-12 shadow-lg w-full">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4 md:gap-6 w-full md:w-auto">
          <div class="w-16 h-16 md:w-20 md:h-20 bg-white rounded-2xl md:rounded-3xl flex items-center justify-center shadow-2xl border-4 border-white/20 flex-none transition-transform hover:scale-105">
            <span class="text-3xl md:text-4xl font-black text-indigo-900 italic">M</span>
          </div>
          
          <div class="text-white text-left flex-1">
            <h1 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">All Works</h1>
            <div class="mt-3 bg-white/10 p-1 rounded-xl backdrop-blur-md inline-flex border border-white/10 gap-1 overflow-x-auto max-w-full">
              <button v-for="tab in ['overview', 'data', 'setup']" :key="tab"
                @click="currentTab = tab" 
                :class="currentTab === tab ? 'bg-white text-indigo-900 shadow-sm' : 'text-white hover:bg-white/5'" 
                class="px-4 md:px-6 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all cursor-pointer whitespace-nowrap">
                {{ tab }}
              </button>
            </div>
          </div>
        </div>
        
        <button @click="showProjectModal = true" class="w-full md:w-auto bg-rose-500 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-600 shadow-xl shadow-rose-200 transition-all active:scale-95">
          <i class="fas fa-plus-circle mr-2"></i> Input New Project
        </button>
      </div>
    </div>

    <div class="w-full max-w-7xl mx-auto p-4 md:p-8 flex flex-col lg:flex-row gap-6 md:gap-8">
      
      <aside class="w-full lg:w-72 space-y-4 flex-none">
        <div class="bg-slate-900 text-white p-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-center italic shadow-lg shadow-slate-200">
          Project Category
        </div>
        
        <div class="bg-white rounded-[2rem] md:rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden font-black italic">
          <button v-for="cat in categories" :key="cat.id" 
            @click="selectCategory(cat.id)"
            :class="selectedCategoryId === cat.id ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-indigo-50'"
            class="w-full p-5 text-left text-[11px] uppercase transition-all flex items-center gap-4 cursor-pointer border-b border-slate-50 last:border-0">
            <div :class="selectedCategoryId === cat.id ? 'bg-white/20' : 'bg-indigo-50'" class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
              <i :class="[cat.icon || 'fas fa-file-alt', selectedCategoryId === cat.id ? 'text-white' : 'text-indigo-600']"></i> 
            </div>
            <span class="flex-1">{{ cat.name }}</span>
            <i v-if="selectedCategoryId === cat.id" class="fas fa-chevron-right text-[10px]"></i>
          </button>
        </div>
      </aside>

      <main class="flex-1 min-w-0 w-full">
        
        <div v-if="currentTab === 'overview'" class="space-y-8 animate-in fade-in duration-500">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4">
            <div v-for="s in stats" :key="s.label" :class="[s.color, s.shadow]" class="p-5 md:p-6 rounded-[1.5rem] md:rounded-[2.5rem] text-white shadow-lg text-center transform transition hover:scale-105">
              <p class="text-xl md:text-2xl font-black leading-none">{{ isLoading ? '...' : s.value }}</p>
              <p class="text-[8px] md:text-[9px] font-bold uppercase tracking-widest mt-2 opacity-80">{{ s.label }}</p>
            </div>
          </div>
          <Works v-if="!isLoading" :series-data="barSeries" chart-type="bar" />
        </div>

        <div v-if="currentTab === 'data'" class="w-full space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
          
          <div v-if="workData.length === 0" class="bg-white p-12 md:p-20 rounded-[2.5rem] md:rounded-[3rem] border border-dashed border-slate-300 text-center">
             <i class="fas fa-folder-open text-5xl text-slate-200 mb-4"></i>
             <p class="text-slate-400 font-black italic uppercase text-sm">No Projects in this category.</p>
             <button @click="showProjectModal = true" class="mt-4 text-indigo-600 font-black text-[10px] uppercase tracking-widest">Add your first project here</button>
          </div>
          
          <div v-for="work in workData" :key="work.id" :id="`project-${work.id}`" 
               class="bg-white p-6 md:p-10 rounded-[2.5rem] md:rounded-[3.5rem] border border-slate-200 mb-6 shadow-sm relative transition-all duration-500 hover:shadow-md">
            
            <div class="flex flex-col md:flex-row justify-between items-start gap-6 mb-8">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <span class="text-[9px] font-black uppercase text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full tracking-widest border border-indigo-100">{{ work.client_name }}</span>
                    <span class="text-[9px] font-black uppercase text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full tracking-widest border border-emerald-100">Rp {{ Number(work.contract_value).toLocaleString() }}</span>
                </div>
                <h3 class="text-2xl md:text-3xl font-black text-indigo-950 uppercase italic leading-tight">{{ work.project_title }}</h3>
                <p class="text-[10px] font-bold text-slate-400 mt-3 italic">Target Deadline: {{ work.deadline }}</p>
              </div>
              
              <div class="w-full md:w-auto text-center md:text-right bg-slate-50 p-5 rounded-3xl border border-slate-100 flex-none">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter mb-1">Project Progress</p>
                <p class="text-4xl font-black text-indigo-600">{{ work.progress_percent }}%</p>
              </div>
            </div>

            <div class="bg-slate-50/50 rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-8 border border-slate-100">
              <p class="text-[10px] font-black text-indigo-950 uppercase tracking-[0.2em] mb-5 flex items-center gap-2">
                <i class="fas fa-tasks text-indigo-600"></i> Milestone & Tasks Detail
              </p>
              
              <div class="flex gap-2 mb-6">
                <input type="text" placeholder="Add specific task detail... (Hit Enter)" 
                  @keyup.enter="addTask(work.id, $event)"
                  class="flex-1 bg-white border border-slate-200 rounded-2xl px-6 py-4 text-xs font-bold shadow-sm outline-none focus:ring-4 ring-indigo-50 transition-all">
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <div v-for="task in work.tasks" :key="task.id" 
                   class="flex items-center gap-4 bg-white p-5 rounded-2xl border border-slate-100 transition-all hover:border-indigo-200 shadow-sm"
                   :class="{'bg-emerald-50/30 border-emerald-100': task.is_completed}">
                    <input type="checkbox" v-model="task.is_completed" @change="toggleTaskStatus(task)"
                      class="w-6 h-6 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer transition-all">
                    <span class="text-[11px] md:text-xs font-bold uppercase transition-all flex-1" 
                      :class="task.is_completed ? 'text-slate-400 line-through' : 'text-slate-700'">
                      {{ task.task_name }}
                    </span>
                 </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="currentTab === 'setup'" class="bg-white p-6 md:p-12 rounded-[2.5rem] md:rounded-[3.5rem] border border-slate-200 shadow-sm">
           <h2 class="text-2xl font-black text-indigo-950 uppercase italic mb-8">System Configuration</h2>
           <div class="bg-slate-50 p-6 md:p-8 rounded-[2rem] md:rounded-[2.5rem] mb-10">
               <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Add New Group/Category</label>
               <div class="flex flex-col md:flex-row gap-4 mt-3">
                  <input v-model="newCategory.name" type="text" placeholder="Category Name (e.g. Hospital Projects)..." class="flex-1 px-6 py-4 bg-white border border-slate-200 rounded-2xl outline-none focus:ring-4 ring-indigo-50 font-bold text-sm transition-all">
                  <button @click="handleAddCategory" :disabled="isSaving" class="bg-indigo-600 text-white px-10 py-4 md:py-0 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-indigo-700 transition-all disabled:opacity-50">
                    Create Group
                  </button>
               </div>
           </div>
        </div>
      </main>
    </div>

    <div v-if="showProjectModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-indigo-950/60 backdrop-blur-md" @click="showProjectModal = false"></div>
        
        <div class="bg-white w-full max-w-2xl rounded-[3rem] p-8 md:p-12 relative z-10 shadow-2xl overflow-y-auto max-h-[90vh] animate-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-black text-indigo-950 uppercase italic leading-none">New Project Data</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-3">Masukan data kontrak dan detail project baru</p>
                </div>
                <button @click="showProjectModal = false" class="text-slate-300 hover:text-rose-500 transition-colors">
                  <i class="fas fa-times-circle text-3xl"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Client Name</label>
                        <input v-model="newProject.client_name" type="text" placeholder="Nama Rumah Sakit / Instansi" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-4 ring-indigo-50 outline-none mt-1 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Project Title</label>
                        <input v-model="newProject.project_title" type="text" placeholder="e.g. Implementasi SIMRS" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-4 ring-indigo-50 outline-none mt-1 transition-all">
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Contract Value (IDR)</label>
                        <input v-model="newProject.contract_value" type="number" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-4 ring-indigo-100 outline-none mt-1 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Deadline</label>
                        <input v-model="newProject.deadline" type="date" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-4 ring-indigo-50 outline-none mt-1 transition-all">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Project Brief / Description</label>
                <textarea v-model="newProject.description" rows="3" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold focus:ring-4 ring-indigo-50 outline-none mt-1 transition-all" placeholder="Detail lingkup pekerjaan..."></textarea>
            </div>

            <div class="mt-10 flex flex-col md:flex-row gap-4">
                <button @click="showProjectModal = false" class="flex-1 py-5 bg-slate-100 text-slate-400 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                <button @click="handleSaveProject" :disabled="isSaving" class="flex-2 py-5 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all disabled:opacity-50">
                    {{ isSaving ? 'Saving Data...' : 'Confirm & Save Project' }}
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
/* Scrollbar Styling agar cantik */
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>