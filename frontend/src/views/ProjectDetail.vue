<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-4">   
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 flex items-center justify-between border-b border-slate-100">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center p-2">
              <img src="/logo-kerjapro.png" class="max-w-full max-h-full object-contain" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">ID-{{ $route.params.id }}</span>
                <h2 class="text-sm font-black text-blue-800 uppercase leading-none">{{ project?.project_title || 'Loading...' }}</h2>
              </div>
              <p class="text-[10px] font-bold text-slate-600 uppercase mt-0.5">
                PT: <span class="text-indigo-600 font-black">{{ project?.affiliated_pt_name || 'Independent' }}</span> | Customer : {{ project?.client_name }}
              </p>
              <p class="text-[8px] text-slate-400 italic">Created by : System</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
             <button @click="$router.push('/projects')" class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-2">
               <i class="fas fa-home"></i> About
             </button>
             <div class="px-4 py-1.5 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 font-black text-xs">
               {{ project?.progress }} %
             </div>
          </div>
        </div>

        <div class="bg-slate-50 px-4 py-1.5 flex flex-wrap gap-1 border-b border-slate-100 items-center">
          <button @click="$router.back()" class="w-7 h-7 bg-slate-400 text-white rounded-md flex items-center justify-center text-[10px]">
            <i class="fas fa-arrow-left"></i>
          </button>
          <button v-for="menu in ['Overview', 'Aktivty', 'Workorder', 'Teamwork', 'Produks', 'Document', 'Support', 'Marketing', 'Purchasing', 'Financial', 'Accounting']" 
            :key="menu"
            @click="subTab = menu.toLowerCase()"
            class="px-3 py-1.5 text-[9px] font-black uppercase transition-all"
            :class="subTab === menu.toLowerCase() ? 'text-blue-800 underline underline-offset-4 decoration-2' : 'text-blue-600 hover:bg-blue-50'">
            {{ menu }}
          </button>
          <button class="ml-auto flex items-center gap-1 text-[9px] font-black uppercase text-slate-600">
            <i class="fas fa-cog"></i> Setup
          </button>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-h-100">
        <div class="flex justify-between items-center px-6 py-3 border-b border-slate-100">
          <div class="flex items-center gap-3 text-blue-900">
            <i class="fas fa-th-large text-sm"></i>
            <h3 class="text-xs font-black uppercase tracking-widest">{{ subTab }}</h3>
          </div>
          <i class="fas fa-calendar-alt text-slate-200 text-xl"></i>
        </div>

        <div class="p-8">
          <!-- 1. OVERVIEW -->
          <div v-if="subTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-10">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Identity</h4>
                  <div class="space-y-3 pl-4">
                    <div class="flex flex-col">
                      <span class="text-[9px] font-bold text-slate-400 uppercase">Project Name</span>
                      <input v-model="project.project_title" @change="updateDetail" 
                        class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
                    </div>
                    <div class="flex flex-col">
                      <span class="text-[9px] font-bold text-slate-400 uppercase">Affiliated PT (Owner)</span>
                      <select v-model="project.company_id" @change="updateDetail" 
                        class="text-[11px] font-black text-blue-800 uppercase bg-transparent border-b border-slate-200 focus:border-blue-500 outline-none transition-all py-1 cursor-pointer appearance-none">
                        <option :value="null">-- INDEPENDENT / NO PT --</option>
                        <option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
                      </select>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-[9px] font-bold text-slate-400 uppercase">Customer / Client</span>
                      <input v-model="project.client_name" @change="updateDetail" 
                        class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
                    </div>
                  </div>
                </div>

                <div>
                  <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Timeline & Value</h4>
                  <div class="space-y-3 pl-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Start Date</span>
                        <input type="date" v-model="project.start_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
                      </div>
                      <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Finish Date</span>
                        <input type="date" v-model="project.finish_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
                      </div>
                    </div>
                    <div class="flex flex-col">
                      <span class="text-[9px] font-bold text-slate-400 uppercase">Contract Value</span>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] font-black text-slate-400">IDR</span>
                        <input type="number" v-model="project.contract_value" @change="updateDetail" class="text-[11px] font-black text-emerald-600 outline-none bg-transparent border-b border-slate-100 w-full">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <h4 class="text-[11px] font-black text-blue-800 mb-3 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Description</h4>
                <textarea v-model="project.description" @change="updateDetail" rows="3" class="w-full pl-4 text-[11px] font-medium text-slate-600 bg-transparent border-none focus:ring-0 resize-none uppercase" placeholder="ADD DESCRIPTION..."></textarea>
              </div>
            </div>

            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 space-y-6">
              <h4 class="text-[11px] font-black text-blue-900 uppercase tracking-tighter text-center mb-4">Work Configuration</h4>
              <div class="space-y-4">
                <div class="flex flex-col">
                  <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Work Category</label>
                  <select v-model="project.category_id" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
                    <option v-for="cat in masterData.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                </div>
                <div class="flex flex-col">
                  <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Current Status</label>
                  <select v-model="project.status" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
                    <option v-for="s in masterData.status" :key="s.id" :value="s.name">{{ s.name }}</option>
                  </select>
                </div>
                <div class="flex flex-col">
                  <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Priority Level</label>
                  <select v-model="project.priority" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-rose-600 uppercase outline-none focus:ring-2 ring-rose-100">
                    <option v-for="p in masterData.priority" :key="p.id" :value="p.name">{{ p.name }}</option>
                  </select>
                </div>
                <div class="flex flex-col">
                  <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Contract Package</label>
                  <select v-model="project.package" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-slate-700 uppercase outline-none">
                    <option v-for="pkg in masterData.package" :key="pkg.id" :value="pkg.name">{{ pkg.name }}</option>
                  </select>
                </div>
              </div>

              <div class="pt-6 border-t border-slate-200 grid grid-cols-2 gap-4">
                <div class="text-center">
                  <p class="text-[8px] font-bold text-slate-400 uppercase">Days Elapsed</p>
                  <p class="text-sm font-black text-slate-800">{{ project?.total_day || '0' }}</p>
                </div>
                <div class="text-center">
                  <p class="text-[8px] font-bold text-slate-400 uppercase">Tasks Count</p>
                  <p class="text-sm font-black text-slate-800">{{ project?.tasks?.length || '0' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. AKTIVTY -->
          <div v-if="subTab === 'aktivty'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-wrap lg:flex-nowrap gap-3 items-center shadow-inner">
              <input v-model="newTaskName" @keyup.enter="handleAddTask" type="text" placeholder="Task Name..." 
                class="flex-1 min-w-50 bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[11px] font-black text-slate-700 uppercase outline-none focus:ring-2 ring-blue-100 shadow-sm">
              <div class="relative flex items-center">
                <i class="fas fa-tag absolute left-4 text-[10px] text-slate-300"></i>
                <input v-model="newTaskCategory" type="text" placeholder="CATEGORY..." 
                  class="w-32 bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-[10px] font-black text-blue-600 uppercase outline-none focus:ring-2 ring-blue-50 shadow-sm">
              </div>
              <select v-model="newTaskPriority" class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-[10px] font-black uppercase outline-none shadow-sm" :class="newTaskPriority === 'High' ? 'text-rose-600' : 'text-slate-500'">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">Urgent</option>
              </select>
              <button @click="handleAddTask" class="bg-blue-800 text-white px-6 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:scale-95 transition-all">Add Task</button>
            </div>

            <div class="space-y-3">
              <div v-if="!project?.tasks?.length" class="py-20 text-center space-y-3 grayscale opacity-30">
                <i class="fas fa-clipboard-list text-4xl text-slate-300"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No activities recorded yet</p>
              </div>
              <div v-for="task in project?.tasks" :key="task.id" class="group bg-white border border-slate-100 p-4 rounded-2xl flex items-center gap-4 transition-all hover:border-blue-200 hover:shadow-md" :class="task.is_completed ? 'bg-slate-50/50' : ''">
                <button @click="handleToggleTask(task)" class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all" :class="task.is_completed ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 bg-white text-transparent hover:border-blue-400'">
                  <i class="fas fa-check text-[10px]"></i>
                </button>
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-black uppercase transition-all" :class="task.is_completed ? 'text-slate-300 line-through' : 'text-slate-700'">{{ task.task_name }}</span>
                    <span class="bg-blue-50 text-blue-600 text-[7px] px-1.5 py-0.5 rounded font-black border border-blue-100 uppercase">{{ task.task_category || 'GENERAL' }}</span>
                    <span v-if="task.priority === 'High'" class="bg-rose-100 text-rose-600 text-[7px] px-1.5 py-0.5 rounded font-black border border-rose-200">URGENT</span>
                  </div>
                  <div class="flex gap-3 mt-1">
                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter"><i class="far fa-clock mr-1"></i> {{ formatDate(task.created_at) }}</span>
                    <span class="text-[8px] font-bold text-blue-400 uppercase tracking-tighter"><i class="far fa-user mr-1"></i> System</span>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="openTaskDetail(task)" class="opacity-0 group-hover:opacity-100 w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center"><i class="fas fa-expand-alt text-[10px]"></i></button>
                  <button @click="handleDeleteTask(task.id)" class="opacity-0 group-hover:opacity-100 w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center"><i class="fas fa-trash-alt text-[10px]"></i></button>
                </div>
              </div>
            </div>

            <div v-if="project?.tasks?.length" class="pt-6 border-t border-slate-100 flex justify-between items-center">
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Completed: {{ project.tasks.filter((t: any) => t.is_completed).length }} / {{ project.tasks.length }} Tasks</p>
              <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden flex">
                <div class="bg-emerald-500 h-full transition-all duration-700" :style="{ width: (project?.progress_percent || 0) + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- 3. WORKORDER -->
          <div v-if="subTab === 'workorder'" class="space-y-6 animate-in slide-in-from-right-4 duration-500">
            <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-800 text-white flex items-center justify-center shadow-lg"><i class="fas fa-file-signature text-xs"></i></div>
                <div>
                  <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Work Order System</h4>
                  <p class="text-[8px] font-bold text-slate-400 uppercase">Official Job Instruction & Costing</p>
                </div>
              </div>
              <button @click="openCreateWO" class="bg-blue-800 text-white px-5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-95 transition-all shadow-lg shadow-blue-200">Create New Order</button>
            </div>

            <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 border-b border-slate-50">
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">WO Number</th>
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Task Description</th>
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">In Charge</th>
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Budget (IDR)</th>
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                    <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="!project?.work_orders?.length">
                    <td colspan="6" class="py-20 text-center">
                      <div class="opacity-20 grayscale flex flex-col items-center">
                        <i class="fas fa-folder-open text-4xl mb-3 text-slate-300"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Official Orders Found</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="wo in project?.work_orders" :key="wo.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                      <span class="text-[10px] font-black text-blue-800 uppercase tracking-tighter">#WO-{{ wo.id }}</span>
                      <p class="text-[7px] text-slate-400 font-bold uppercase mt-0.5">{{ formatDate(wo.created_at) }}</p>
                    </td>
                    <td class="px-6 py-4">
                      <p class="text-[11px] font-black text-slate-700 uppercase leading-tight">{{ wo.title }}</p>
                      <p class="text-[8px] text-slate-400 font-medium line-clamp-1 italic mt-0.5">{{ wo.description || 'No instruction' }}</p>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200"><i class="fas fa-user text-[8px] text-slate-400"></i></div>
                        <span class="text-[9px] font-bold text-slate-600 uppercase">{{ wo.pic_name || 'Unassigned' }}</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-right"><span class="text-[11px] font-black text-emerald-600 tracking-tight">{{ formatCurrency(wo.budget) }}</span></td>
                    <td class="px-6 py-4 text-center"><span :class="statusClass(wo.status)" class="px-3 py-1 rounded-lg text-[8px] font-black uppercase border">{{ wo.status }}</span></td>
                    <td class="px-6 py-4 text-center">
                      <div class="flex justify-center gap-2">
                        <button @click="editWO(wo)" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all"><i class="fas fa-pencil-alt text-[9px]"></i></button>
                        <button @click="deleteWO(wo.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all"><i class="fas fa-trash text-[9px]"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 4. TEAMWORK -->
          <div v-if="subTab === 'teamwork'" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500">
            <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-100"><i class="fas fa-users-cog text-xs"></i></div>
                <div>
                  <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Resource Management</h4>
                  <p class="text-[8px] font-bold text-slate-400 uppercase">Assign & Monitor Team Distribution</p>
                </div>
              </div>
              <button @click="openAddMemberModal" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-95 transition-all shadow-lg shadow-indigo-100"><i class="fas fa-user-plus mr-2"></i> Add Member</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-if="!project?.team?.length" class="col-span-full py-20 text-center opacity-30 grayscale bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                <i class="fas fa-user-friends text-4xl mb-3 text-slate-300"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No team members assigned to this project</p>
              </div>
              <div v-for="member in project?.team" :key="member.id" class="bg-white border border-slate-100 p-5 rounded-3xl shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-sm uppercase shadow-inner">{{ member.name.substring(0, 2) }}</div>
                    <div>
                      <h5 class="text-[12px] font-black text-slate-800 uppercase leading-none">{{ member.name }}</h5>
                      <span class="text-[8px] font-black text-indigo-500 uppercase tracking-widest bg-indigo-50 px-2 py-0.5 rounded mt-1.5 inline-block border border-indigo-100">{{ member.role }}</span>
                    </div>
                  </div>
                  <button @click="removeMember(member.id)" class="opacity-0 group-hover:opacity-100 transition-all text-slate-300 hover:text-rose-500 p-1"><i class="fas fa-times-circle text-lg"></i></button>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-50 grid grid-cols-2 gap-4">
                  <div class="space-y-1">
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Contribution</p>
                    <div class="flex items-center gap-2"><p class="text-[11px] font-black text-slate-700">{{ member.tasks_count || 0 }} Tasks</p></div>
                  </div>
                  <div class="text-right space-y-1">
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Performance</p>
                    <span class="text-[9px] font-black text-emerald-500 uppercase italic">On Track</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 5. PRODUKS -->
          <div v-if="subTab === 'produks'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
              <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center"><i class="fas fa-box-open text-xs"></i></div>
                <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">Submit Deliverable</h4>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="prodForm.title" type="text" placeholder="TITLE (E.G. STAGING URL)" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none focus:ring-2 ring-emerald-100 uppercase">
                <select v-model="prodForm.type" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
                  <option value="Link">Web Link / URL</option>
                  <option value="Credentials">Credentials / Login</option>
                  <option value="File">File Path / Drive</option>
                  <option value="Server">Server Access</option>
                </select>
                <input v-model="prodForm.version" type="text" placeholder="VERSION (E.G. 1.0.1)" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
              </div>
              <textarea v-model="prodForm.content" placeholder="CONTENT OR URL..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none focus:ring-2 ring-emerald-50 uppercase" rows="2"></textarea>
              <button @click="handleSaveProduction" class="w-full bg-emerald-600 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-[0.98] transition-all">Save Production Output</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="prod in project?.productions" :key="prod.id" class="bg-white border border-slate-100 p-5 rounded-3xl flex items-start gap-4 hover:shadow-md transition-all relative group">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner text-lg" :class="prod.type === 'Link' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'">
                  <i :class="prod.type === 'Link' ? 'fas fa-link' : 'fas fa-key'"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h5 class="text-[11px] font-black text-slate-800 uppercase truncate">{{ prod.title }}</h5>
                    <span class="text-[7px] font-black bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded uppercase">v{{ prod.version }}</span>
                  </div>
                  <p class="text-[10px] font-mono text-blue-600 truncate mt-1 bg-blue-50/50 p-1 rounded">{{ prod.content }}</p>
                  <div class="mt-3 flex items-center gap-3">
                    <span class="text-[8px] font-bold text-slate-400 uppercase"><i class="fas fa-user-circle mr-1"></i> {{ prod.user_name || 'System' }}</span>
                    <span class="text-[8px] font-bold text-slate-400 uppercase"><i class="far fa-calendar-alt mr-1"></i> {{ formatDate(prod.created_at) }}</span>
                  </div>
                </div>
                <button @click="handleDeleteProduction(prod.id)" class="opacity-0 group-hover:opacity-100 absolute top-4 right-4 text-rose-300 hover:text-rose-500"><i class="fas fa-trash-alt"></i></button>
              </div>
            </div>
          </div>

          <!-- 6. DOCUMENT -->
          <div v-if="subTab === 'document'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center"><i class="fas fa-file-upload text-xs"></i></div>
                <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">Upload Project Document</h4>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Document Title</label>
                  <input v-model="docForm.title" type="text" placeholder="E.G. SURAT KONTRAK / FSD" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase shadow-inner">
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Select File</label>
                  <input type="file" @change="handleFileChange" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-[10px] font-bold outline-none file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>
              </div>
              <button @click="uploadDoc" :disabled="loadingDoc" class="w-full bg-blue-600 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:scale-[0.98] transition-all disabled:opacity-50">
                {{ loadingDoc ? 'UPLOADING...' : 'SAVE DOCUMENT' }}
              </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
              <div v-for="doc in project?.documents" :key="doc.id" class="bg-white border border-slate-100 p-4 rounded-3xl text-center hover:shadow-md transition-all group relative">
                <div class="w-16 h-16 mx-auto mb-3 bg-slate-50 rounded-2xl flex items-center justify-center text-2xl text-slate-400 group-hover:scale-110 transition-transform">
                  <i v-if="doc.file_type === 'pdf'" class="fas fa-file-pdf text-rose-500"></i>
                  <i v-else-if="['doc', 'docx'].includes(doc.file_type)" class="fas fa-file-word text-blue-500"></i>
                  <i v-else-if="['jpg', 'jpeg', 'png'].includes(doc.file_type)" class="fas fa-file-image text-emerald-500"></i>
                  <i v-else class="fas fa-file text-slate-300"></i>
                </div>
                <h5 class="text-[10px] font-black text-slate-800 uppercase truncate px-2">{{ doc.title }}</h5>
                <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">{{ doc.file_type }} • {{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB</p>
                <div class="mt-4 flex gap-2">
                  <a :href="'/uploads/' + doc.file_path" target="_blank" class="flex-1 bg-slate-50 py-1.5 rounded-lg text-[8px] font-black text-slate-500 uppercase hover:bg-blue-600 hover:text-white transition-colors">Open</a>
                  <button @click="deleteDoc(doc.id)" class="bg-slate-50 px-2 rounded-lg text-slate-300 hover:text-rose-500"><i class="fas fa-trash-alt text-[10px]"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- 7. SUPPORT -->
          <div v-if="subTab === 'support'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shadow-lg shadow-rose-100"><i class="fas fa-headset text-xs"></i></div>
                <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">New Support Ticket</h4>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="md:col-span-2">
                  <input v-model="supportForm.subject" type="text" placeholder="ISSUE SUBJECT (E.G. LOGIN ERROR)" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase shadow-inner">
                </div>
                <select v-model="supportForm.priority" class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
                  <option value="Low">Low Priority</option>
                  <option value="Medium">Medium Priority</option>
                  <option value="High">High Priority</option>
                  <option value="Urgent">Urgent / Critical</option>
                </select>
              </div>
              <textarea v-model="supportForm.message" placeholder="DESCRIBE THE ISSUE IN DETAIL..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none focus:ring-2 ring-rose-50 uppercase mb-4" rows="3"></textarea>
              <button @click="handleSaveSupport" class="w-full bg-rose-600 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-100 hover:scale-[0.98] transition-all">Open Support Ticket</button>
            </div>
            <div class="space-y-3">
              <div v-for="ticket in project?.supports" :key="ticket.id" class="bg-white border border-slate-100 p-5 rounded-3xl flex items-center gap-4 hover:border-rose-200 transition-all group shadow-sm">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm shadow-inner" :class="ticket.status === 'Resolved' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500'">
                  <i :class="ticket.status === 'Resolved' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h5 class="text-[11px] font-black text-slate-800 uppercase truncate">{{ ticket.subject }}</h5>
                    <span :class="priorityColor(ticket.priority)" class="text-[7px] font-black px-1.5 py-0.5 rounded border uppercase">{{ ticket.priority }}</span>
                  </div>
                  <p class="text-[10px] text-slate-500 truncate mt-1 lowercase">{{ ticket.message }}</p>
                </div>
                <div class="text-right flex flex-col items-end gap-2">
                  <select @change="updateTicketStatus(ticket.id, $event)" class="text-[9px] font-black uppercase bg-slate-100 border-none rounded-lg px-2 py-1 outline-none">
                    <option :selected="ticket.status === 'Open'" value="Open">Open</option>
                    <option :selected="ticket.status === 'In-Progress'" value="In-Progress">In-Progress</option>
                    <option :selected="ticket.status === 'Resolved'" value="Resolved">Resolved</option>
                  </select>
                  <span class="text-[8px] font-bold text-slate-300 uppercase italic">{{ formatDate(ticket.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 8. MARKETING -->
          <div v-if="subTab === 'marketing'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-orange-500 text-white flex items-center justify-center shadow-lg shadow-orange-100"><i class="fas fa-bullhorn text-xs"></i></div>
                <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">New Marketing Opportunity</h4>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="md:col-span-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Opportunity Title</label>
                  <input v-model="marketForm.title" type="text" placeholder="E.G. ADD MOBILE APP" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase shadow-inner">
                </div>
                <div>
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Type</label>
                  <select v-model="marketForm.type" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase">
                    <option value="Upselling">Upselling</option>
                    <option value="New Feature">New Feature</option>
                    <option value="Maintenance">Maintenance Contract</option>
                    <option value="Testimonial">Testimonial Request</option>
                  </select>
                </div>
                <div>
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Next Follow Up</label>
                  <input v-model="marketForm.next_follow_up" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none">
                </div>
              </div>
              <textarea v-model="marketForm.notes" placeholder="STRATEGY NOTES..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none focus:ring-2 ring-orange-50 uppercase mb-4" rows="2"></textarea>
              <button @click="handleSaveMarketing" class="w-full bg-orange-500 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-orange-100 hover:scale-[0.98] transition-all">Create Opportunity</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="item in project?.marketings" :key="item.id" class="bg-white border border-slate-100 p-5 rounded-3xl flex flex-col gap-3 hover:shadow-md transition-all relative group">
                <div class="flex justify-between items-start">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fas fa-rocket text-sm"></i></div>
                    <div>
                      <h5 class="text-[11px] font-black text-slate-800 uppercase">{{ item.title }}</h5>
                      <span class="text-[8px] font-black text-orange-500 bg-orange-50 px-2 py-0.5 rounded border border-orange-100 uppercase">{{ item.type }}</span>
                    </div>
                  </div>
                  <span class="text-[8px] font-black text-slate-400 bg-slate-50 px-3 py-1 rounded-full border border-slate-100 uppercase">{{ item.status }}</span>
                </div>
                <div class="mt-2 text-[10px] text-slate-500 font-medium bg-slate-50/50 p-3 rounded-xl lowercase">{{ item.notes }}</div>
                <div class="flex justify-between items-center mt-2 pt-2 border-t border-slate-50">
                  <div class="flex flex-col">
                    <span class="text-[7px] font-black text-slate-400 uppercase">Next Contact</span>
                    <span class="text-[9px] font-black text-slate-700">{{ item.next_follow_up || '-' }}</span>
                  </div>
                  <button @click="handleDeleteMarketing(item.id)" class="opacity-0 group-hover:opacity-100 text-rose-300 hover:text-rose-500 transition-all"><i class="fas fa-trash-alt text-[10px]"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- 9. PURCHASING -->
          <div v-if="subTab === 'purchasing'" class="space-y-6 animate-in fade-in duration-500">
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-100"><i class="fas fa-shopping-cart text-xs"></i></div>
                <h4 class="text-xs font-black uppercase text-slate-800 tracking-widest">Add Purchase Record</h4>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div class="md:col-span-2">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Item / Service Name</label>
                  <input v-model="purchaseForm.item_name" type="text" placeholder="E.G. SERVER CLOUD / LICENSE" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none uppercase shadow-inner focus:ring-2 ring-emerald-50">
                </div>
                <div>
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Price (Unit)</label>
                  <input v-model="purchaseForm.amount" type="number" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none focus:ring-2 ring-emerald-50">
                </div>
                <div>
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Qty</label>
                  <input v-model="purchaseForm.quantity" type="number" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold outline-none focus:ring-2 ring-emerald-50">
                </div>
              </div>
              <button @click="handleSavePurchase" class="w-full bg-emerald-600 text-white py-3.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:scale-[0.98] transition-all active:scale-95">Save Transaction</button>
            </div>
            <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm">
              <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/80">
                  <tr>
                    <th class="p-4 px-6 text-[9px] font-black text-slate-400 uppercase tracking-widest">Item Description</th>
                    <th class="p-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Price x Qty</th>
                    <th class="p-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Total Amount</th>
                    <th class="p-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                    <th class="p-4 px-6 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="!project?.purchasings?.length">
                    <td colspan="5" class="py-12 text-center">
                      <div class="opacity-20 grayscale flex flex-col items-center">
                        <i class="fas fa-receipt text-3xl mb-2 text-slate-300"></i>
                        <p class="text-[9px] font-black text-slate-400 uppercase">No purchase records found</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="item in project?.purchasings" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 px-6">
                      <p class="text-[11px] font-black text-slate-800 uppercase">{{ item.item_name }}</p>
                      <p class="text-[8px] font-bold text-slate-400 uppercase">{{ formatDate(item.purchase_date) }}</p>
                    </td>
                    <td class="p-4 text-[10px] font-bold text-slate-500">{{ formatCurrency(item.amount) }} x {{ item.quantity }}</td>
                    <td class="p-4 text-right"><p class="text-[11px] font-black text-rose-600 italic">{{ formatCurrency(item.total_price) }}</p></td>
                    <td class="p-4 text-center"><span class="text-[8px] font-black px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 uppercase border border-emerald-100 shadow-sm">{{ item.status || 'PAID' }}</span></td>
                    <td class="p-4 px-6 text-center"><button @click="handleDeletePurchase(item.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-400 hover:bg-rose-500 hover:text-white transition-all flex items-center justify-center mx-auto"><i class="fas fa-trash-alt text-[10px]"></i></button></td>
                  </tr>
                </tbody>
                <tfoot v-if="project?.purchasings?.length" class="bg-slate-50/30">
                  <tr>
                    <td colspan="2" class="p-4 px-6 text-[9px] font-black text-slate-500 uppercase">Subtotal Purchasing</td>
                    <td class="p-4 text-right text-[11px] font-black text-slate-900 border-t border-slate-100">{{ formatCurrency(project.purchasings.reduce((t: any, p: any) => t + parseFloat(p.total_price), 0)) }}</td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="mt-6 bg-rose-50 border border-rose-100 p-6 rounded-3xl flex justify-between items-center shadow-inner">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-rose-500 shadow-sm"><i class="fas fa-calculator"></i></div>
                <div>
                  <h5 class="text-[10px] font-black text-rose-900 uppercase tracking-widest">Total Project Spending</h5>
                  <p class="text-[8px] font-bold text-rose-400 uppercase leading-none">Accumulated from Workorders & Purchasing</p>
                </div>
              </div>
              <div class="text-right">
                <span class="text-lg font-black text-rose-600 italic">{{ formatCurrency(calculateTotalExpenses()) }}</span>
              </div>
            </div>
          </div>

          <!-- 10. FINANCIAL -->
          <!-- 10. FINANCIAL (CASHBOOK & INVOICING) -->
          <!-- ========================================== -->
          <!-- 10. FINANCIAL (CASHBOOK & INVOICING)       -->
          <!-- ========================================== -->
          <div v-if="subTab === 'financial'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in zoom-in-95 duration-500">
            
            <!-- SIDEBAR NAVIGASI INTERNAL FINANCIAL (Modern Style) -->
            <div class="lg:col-span-3 space-y-6">
              <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60 sticky top-4">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 ml-2">Finance Modules</h3>
                <div class="space-y-3">
                  <button @click="activeFinanceNav = 'overview'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" 
                    :class="activeFinanceNav === 'overview' ? 'bg-slate-900 text-white shadow-xl shadow-slate-200' : 'hover:bg-slate-50 text-slate-500'">
                    <div class="flex items-center gap-4">
                      <i class="fas fa-chart-pie text-sm" :class="activeFinanceNav === 'overview' ? 'text-indigo-400' : 'text-slate-400'"></i>
                      <span class="text-[11px] font-black uppercase tracking-tight">Analytics</span>
                    </div>
                    <i v-if="activeFinanceNav === 'overview'" class="fas fa-chevron-right text-[10px] text-slate-500"></i>
                  </button>

                  <button @click="activeFinanceNav = 'transaksi'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" 
                    :class="activeFinanceNav === 'transaksi' ? 'bg-slate-900 text-white shadow-xl shadow-slate-200' : 'hover:bg-slate-50 text-slate-500'">
                    <div class="flex items-center gap-4">
                      <i class="fas fa-exchange-alt text-sm" :class="activeFinanceNav === 'transaksi' ? 'text-emerald-400' : 'text-slate-400'"></i>
                      <span class="text-[11px] font-black uppercase tracking-tight">Cashbook</span>
                    </div>
                    <i v-if="activeFinanceNav === 'transaksi'" class="fas fa-chevron-right text-[10px] text-slate-500"></i>
                  </button>

                  <button @click="activeFinanceNav = 'invoicing'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" 
                    :class="activeFinanceNav === 'invoicing' ? 'bg-slate-900 text-white shadow-xl shadow-slate-200' : 'hover:bg-slate-50 text-slate-500'">
                    <div class="flex items-center gap-4">
                      <i class="fas fa-file-invoice-dollar text-sm" :class="activeFinanceNav === 'invoicing' ? 'text-blue-400' : 'text-slate-400'"></i>
                      <span class="text-[11px] font-black uppercase tracking-tight">Invoicing</span>
                    </div>
                    <i v-if="activeFinanceNav === 'invoicing'" class="fas fa-chevron-right text-[10px] text-slate-500"></i>
                  </button>
                </div>

                <div class="mt-8 p-6 rounded-[2rem] bg-indigo-50/50 border border-indigo-100/50">
                  <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-1 text-center">Net Position</p>
                  <p class="text-sm font-black text-indigo-700 text-center tracking-tighter">{{ formatCurrency((project?.contract_value || 0) - calculateTotalExpenses()) }}</p>
                </div>
              </div>
            </div>

            <!-- CONTENT AREA FINANCIAL -->
            <div class="lg:col-span-9 space-y-8">
              
              <!-- 10.1 FINANCIAL ANALYSIS (OVERVIEW) -->
              <div v-if="activeFinanceNav === 'overview'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                   <div class="bg-white border border-slate-100 p-6 rounded-[2.5rem] shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                      <div class="absolute -right-4 -top-4 w-20 h-20 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 relative">Gross Revenue</p>
                      <h3 class="text-xl font-black text-slate-800 tracking-tighter relative">{{ formatCurrency(project?.contract_value) }}</h3>
                      <div class="mt-4 flex items-center gap-2 text-[8px] font-black text-indigo-500 uppercase italic relative"><i class="fas fa-file-contract"></i> Contract</div>
                   </div>

                   <div class="bg-white border border-slate-100 p-6 rounded-[2.5rem] shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                      <div class="absolute -right-4 -top-4 w-20 h-20 bg-rose-50 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 relative">Burn Rate</p>
                      <h3 class="text-xl font-black text-rose-600 tracking-tighter relative">{{ formatCurrency(calculateTotalExpenses()) }}</h3>
                      <div class="mt-4 flex items-center gap-2 text-[8px] font-black text-rose-400 uppercase italic relative"><i class="fas fa-fire"></i> Expenses</div>
                   </div>

                   <div class="p-6 rounded-[2.5rem] shadow-xl relative overflow-hidden text-white group" :class="calculateMargin() > 0 ? 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-100' : 'bg-gradient-to-br from-rose-500 to-pink-600 shadow-rose-100'">
                      <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-1000"></div>
                      <p class="text-[9px] font-black opacity-80 uppercase tracking-widest mb-2">Net Profit</p>
                      <h3 class="text-xl font-black tracking-tighter">{{ formatCurrency((project?.contract_value || 0) - calculateTotalExpenses()) }}</h3>
                      <p class="mt-4 inline-flex items-center gap-2 text-[9px] font-black uppercase bg-white/20 px-3 py-1 rounded-full"><i class="fas fa-percentage"></i> Margin: {{ calculateMargin().toFixed(1) }}%</p>
                   </div>

                   <div class="bg-slate-900 p-6 rounded-[2.5rem] shadow-xl shadow-slate-200 relative overflow-hidden group border border-slate-800">
                      <div class="absolute -right-2 -bottom-2 text-white opacity-5 group-hover:rotate-12 transition-transform duration-700"><i class="fas fa-vault text-6xl"></i></div>
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Available Cash</p>
                      <h3 class="text-xl font-black text-emerald-400 tracking-tighter">{{ formatCurrency((project?.contract_value || 0) - calculateTotalExpenses()) }}</h3>
                      <div class="mt-4 flex items-center gap-2 text-[8px] font-black text-slate-500 uppercase italic"><i class="fas fa-shield-alt"></i> Safety Limit</div>
                   </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm">
                   <div class="p-8 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center">
                     <div>
                       <h4 class="text-sm font-black uppercase text-slate-800 tracking-widest">Internal Spending Breakdown</h4>
                       <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Real-time cost accumulation from activity modules</p>
                     </div>
                     <i class="fas fa-list-check text-slate-200 text-2xl"></i>
                   </div>
                   <table class="w-full text-left">
                      <thead class="bg-slate-50/30 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        <tr>
                          <th class="p-6">Module & Item Name</th>
                          <th class="p-6 text-right">Value (IDR)</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-50">
                        <tr v-for="wo in project?.work_orders" :key="'overview-wo-'+wo.id" class="group hover:bg-slate-50/50 transition-colors">
                           <td class="p-6">
                             <div class="flex items-center gap-4 font-black text-xs text-slate-700 uppercase">
                               <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform"><i class="fas fa-tools text-[10px]"></i></div>
                               <span>{{ wo.title }}</span>
                               <span class="text-[8px] px-2 py-0.5 bg-slate-100 text-slate-400 rounded-full border border-slate-200">Workorder</span>
                             </div>
                           </td>
                           <td class="p-6 text-right font-black italic text-slate-800">{{ formatCurrency(wo.budget) }}</td>
                        </tr>
                        <tr v-for="p in project?.purchasings" :key="'overview-pur-'+p.id" class="group hover:bg-slate-50/50 transition-colors">
                           <td class="p-6">
                             <div class="flex items-center gap-4 font-black text-xs text-slate-700 uppercase">
                               <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform"><i class="fas fa-shopping-cart text-[10px]"></i></div>
                               <span>{{ p.item_name }}</span>
                               <span class="text-[8px] px-2 py-0.5 bg-slate-100 text-slate-400 rounded-full border border-slate-200">Purchasing</span>
                             </div>
                           </td>
                           <td class="p-6 text-right font-black italic text-slate-800">{{ formatCurrency(p.total_price) }}</td>
                        </tr>
                      </tbody>
                   </table>
                </div>
              </div>

              <!-- 10.2 CASHBOOK (TRANSAKSI) -->
              <div v-if="activeFinanceNav === 'transaksi'" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex justify-between items-center">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-100"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                      <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Cashbook Ledger</h3>
                      <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Operational Flow Records</p>
                    </div>
                  </div>
                  <button @click="showTxModal = true" class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-slate-200 hover:bg-slate-800 hover:scale-[0.98] transition-all">
                    <i class="fas fa-plus-circle mr-2"></i> Record Transaction
                  </button>
                </div>

                <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden p-2">
                   <table class="w-full text-left">
                      <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 tracking-widest">
                        <tr>
                          <th class="p-6">Date / Ref</th>
                          <th class="p-6">Account & Details</th>
                          <th class="p-6 text-right">Amount (In/Out)</th>
                          <th class="p-6 text-center">Status</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-50 text-[11px] font-bold text-slate-600">
                        <tr v-for="trx in projectTransactions" :key="trx.id" class="hover:bg-slate-50/50 transition-colors">
                           <td class="p-6 uppercase">
                             <p class="text-slate-800 font-black">{{ trx.transaction_date }}</p>
                             <p class="text-[8px] text-slate-400 font-bold mt-1">{{ trx.transaction_number }}</p>
                           </td>
                           <td class="p-6">
                             <p class="text-slate-700 font-black uppercase truncate max-w-[200px]">{{ trx.description }}</p>
                             <div class="mt-1.5 flex items-center gap-2">
                               <span class="text-[8px] px-2 py-0.5 bg-indigo-50 text-indigo-500 rounded border border-indigo-100 uppercase font-black">[{{ trx.coa_code }}] {{ trx.coa_name }}</span>
                               <span class="text-[8px] text-slate-300 font-black uppercase">via {{ trx.method }}</span>
                             </div>
                           </td>
                           <td class="p-6 text-right font-black italic text-sm" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">
                             {{ trx.type === 'inflow' ? '+' : '-' }} {{ formatCurrency(trx.amount) }}
                           </td>
                           <td class="p-6 text-center">
                             <span class="px-3 py-1.5 rounded-xl text-[8px] font-black border" :class="trx.status === 'Approved' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'">
                               {{ trx.status }}
                             </span>
                           </td>
                        </tr>
                      </tbody>
                   </table>
                </div>
              </div>

              <!-- 10.3 INVOICING / BILLING -->
              <div v-if="activeFinanceNav === 'invoicing'" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-[2.5rem] text-white shadow-xl shadow-blue-100 relative overflow-hidden">
                      <i class="fas fa-check-circle absolute right-8 bottom-8 text-6xl opacity-10"></i>
                      <p class="text-[9px] font-black opacity-70 uppercase tracking-widest mb-2">Total Paid Invoices</p>
                      <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(calculateTotalInvoiced('Paid')) }}</h3>
                    </div>
                    <div class="bg-white border-2 border-rose-100 p-8 rounded-[2.5rem] shadow-sm relative overflow-hidden">
                      <i class="fas fa-clock absolute right-8 bottom-8 text-6xl text-rose-50 opacity-50"></i>
                      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Awaiting Payment</p>
                      <h3 class="text-2xl font-black text-rose-500 tracking-tighter">{{ formatCurrency(calculateTotalInvoiced('Unpaid')) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm space-y-6">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h4 class="text-sm font-black uppercase text-slate-800 tracking-widest">Generate Official Invoice</h4>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-1.5">
                      <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Invoice Label</label>
                      <input v-model="invForm.title" placeholder="E.G. PROGRESS 50%" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase focus:ring-2 ring-blue-100">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Total Amount</label>
                      <input v-model="invForm.amount" type="number" placeholder="0.00" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase focus:ring-2 ring-blue-100">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Due Date</label>
                      <input v-model="invForm.due_date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase focus:ring-2 ring-blue-100">
                    </div>
                  </div>

                  <button @click="handleSaveInvoice" class="w-full bg-slate-900 text-white py-5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 hover:scale-[0.99] transition-all">
                    Generate & Notify Client
                  </button>
                </div>

                <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm p-2">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 tracking-widest">
                      <tr>
                        <th class="p-6">Invoice ID</th>
                        <th class="p-6">Description</th>
                        <th class="p-6 text-right">Amount</th>
                        <th class="p-6 text-center">Status</th>
                        <th class="p-6 text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-[11px] font-bold text-slate-600">
                      <tr v-for="inv in project?.invoices" :key="inv.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="p-6 font-mono text-blue-600 font-black tracking-tight">{{ inv.invoice_number }}</td>
                        <td class="p-6 font-black uppercase text-slate-800">{{ inv.title }}</td>
                        <td class="p-6 text-right font-black italic">{{ formatCurrency(inv.amount) }}</td>
                        <td class="p-6 text-center">
                           <span class="px-3 py-1.5 rounded-full text-[8px] font-black uppercase border" :class="inv.status === 'Paid' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'">{{ inv.status }}</span>
                        </td>
                        <td class="p-6 text-center">
                          <button v-if="inv.status !== 'Paid'" @click="updateInvStatus(inv.id, 'Paid')" class="text-emerald-500 font-black uppercase text-[10px] hover:underline flex items-center justify-center gap-2 mx-auto transition-all"><i class="fas fa-check-circle"></i> Mark Paid</button>
                          <span v-else class="text-slate-300 italic text-[10px]">Settled</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- 11. ACCOUNTING -->
          <div v-if="subTab === 'accounting'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
            <div class="lg:col-span-3 space-y-6">
              <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Business Entities</h3>
                <div class="space-y-2">
                  <button @click="activeAccEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                    <i class="fas fa-globe text-sm w-4"></i> <span class="text-[11px] font-black uppercase">Consolidated (All)</span>
                  </button>
                  <button @click="activeAccEntity = 'personal'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === 'personal' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                    <i class="fas fa-user-tie text-sm w-4"></i> <span class="text-[11px] font-black uppercase">Personal / General</span>
                  </button>
                  <button v-for="pt in allCompanies" :key="pt.id" @click="activeAccEntity = pt.id" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === pt.id ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                    <i class="fas fa-building text-sm w-4"></i> <span class="text-[11px] font-black uppercase truncate">{{ pt.name }}</span>
                  </button>
                </div>
              </div>

              <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Account Categories</h3>
                <div class="space-y-2">
                  <button v-for="cat in coaCategories" :key="cat.id" @click="activeAccCategory = cat.id" class="w-full flex items-center justify-between px-5 py-3 rounded-2xl transition-all text-left group" :class="activeAccCategory === cat.id ? 'bg-slate-900 border border-slate-800' : 'hover:bg-slate-50 border border-transparent'">
                    <div class="flex items-center gap-4">
                      <i class="fas text-sm" :class="[cat.icon, activeAccCategory === cat.id ? 'text-white' : cat.color]"></i>
                      <span class="text-[10px] font-black uppercase tracking-tight" :class="activeAccCategory === cat.id ? 'text-white' : 'text-slate-500'">{{ cat.label }}</span>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <div class="lg:col-span-9 flex flex-col gap-6 min-h-150">
              <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="relative w-full md:w-96">
                  <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                  <input v-model="searchCOA" type="text" placeholder="Cari Kode atau Nama Akun..." class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest text-slate-700">
                </div>
                <button @click="openCoaModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
                  <i class="fas fa-plus"></i> Tambah COA
                </button>
              </div>

              <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
                <div class="overflow-x-auto flex-1 p-2">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 rounded-t-[2.5rem]">
                      <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        <th class="p-6 rounded-tl-4xl">Account Code & Name</th>
                        <th class="p-6">Category Type</th>
                        <th class="p-6">Affiliated Entity</th>
                        <th class="p-6 text-right rounded-tr-4xl">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                      <tr v-if="filteredCOAs.length === 0">
                        <td colspan="4" class="p-20 text-center text-slate-300">
                          <i class="fas fa-folder-open text-4xl mb-4"></i>
                          <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada akun COA ditemukan</p>
                        </td>
                      </tr>
                      <tr v-for="coa in filteredCOAs" :key="coa.id" class="hover:bg-slate-50/50 transition-colors group">
                        <td class="p-6">
                          <div class="flex items-center gap-4">
                            <div class="px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg text-[10px] font-black text-indigo-600">{{ coa.code }}</div>
                            <p class="text-xs font-black text-slate-700 uppercase">{{ coa.name }}</p>
                          </div>
                        </td>
                        <td class="p-6">
                          <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                            <i class="fas fa-circle text-[8px] mr-1" :class="{'text-emerald-500': coa.category === 'Asset', 'text-rose-500': coa.category === 'Liability' || coa.category === 'Expense', 'text-indigo-500': coa.category === 'Equity', 'text-emerald-400': coa.category === 'Revenue'}"></i>
                            {{ coa.category }}
                          </span>
                        </td>
                        <td class="p-6">
                          <span v-if="coa.pt_id" class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full border border-slate-200 text-[9px] font-black text-slate-600 uppercase tracking-widest"><i class="fas fa-building text-slate-400"></i> PT terikat</span>
                          <span v-else class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 rounded-full border border-indigo-100 text-[9px] font-black text-indigo-500 uppercase tracking-widest"><i class="fas fa-user-tie"></i> Personal / General</span>
                        </td>
                        <td class="p-6 text-right">
                          <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openCoaModal(coa)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                            <button @click="handleDeleteCOA(coa.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-trash text-[10px]"></i></button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>
    
    <!-- MODAL ACTIVITY: TASK DOCUMENTATION -->
    <div v-if="showTaskModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-800 text-white flex items-center justify-center text-xs shadow-lg shadow-blue-200"><i class="fas fa-file-alt"></i></div>
            <h4 class="text-xs font-black uppercase text-blue-900 tracking-widest">Activity Documentation</h4>
          </div>
          <button @click="showTaskModal = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-200 transition-colors"><i class="fas fa-times text-slate-400"></i></button>
        </div>
        <div class="p-8 space-y-6">
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Task Title</label>
            <div class="text-[13px] font-black text-slate-800 uppercase px-4 py-3 bg-slate-50 rounded-xl border border-slate-100">{{ activeTask?.task_name }}</div>
          </div>
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Documentation / Tech Notes</label>
            <textarea v-model="activeTask.description" rows="6" class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none focus:ring-2 ring-blue-50 transition-all" placeholder="Paste your code snippet or tech notes here..."></textarea>
          </div>
        </div>
        <div class="p-6 bg-slate-50 flex gap-3">
          <button @click="saveTaskDetail" class="flex-1 bg-blue-800 text-white py-3.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-200 transition-all active:scale-95">Update Documentation</button>
        </div>
      </div>
    </div>

    <!-- MODAL WORKORDER -->
    <div v-if="showWOModal" class="fixed inset-0 z-110 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
      <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h4 class="text-xs font-black uppercase text-blue-900 tracking-widest">Work Order Assignment</h4>
          <button @click="showWOModal = false"><i class="fas fa-times text-slate-400"></i></button>
        </div>
        <div class="p-8 grid grid-cols-2 gap-6">
          <div class="col-span-2 space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Instruction Title</label>
            <input v-model="woForm.title" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase">
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Assign To (PIC)</label>
            <input v-model="woForm.pic_name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase">
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Budget Estimation</label>
            <input v-model="woForm.budget" type="number" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-emerald-600 outline-none">
          </div>
          <div class="col-span-2 space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase">Detailed Instruction</label>
            <textarea v-model="woForm.description" rows="4" class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] font-medium text-slate-600 outline-none uppercase"></textarea>
          </div>
        </div>
        <div class="p-6 bg-slate-50 flex gap-3">
          <button @click="saveWO" class="flex-1 bg-[#2E3A8C] text-white py-3.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-200">{{ woForm.id ? 'Update Order' : 'Dispatch Work Order' }}</button>
        </div>
      </div>
    </div>

    <!-- MODAL TEAMWORK -->
    <div v-if="showTeamModal" class="fixed inset-0 z-120 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
      <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h4 class="text-xs font-black uppercase text-indigo-900 tracking-widest">Assign Member to Project</h4>
          <button @click="showTeamModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-5">
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Select Personnel</label>
            <select v-model="teamForm.user_id" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase shadow-inner">
              <option :value="null" disabled>Choose User...</option>
              <option v-for="u in allUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Project Role / Job Desk</label>
            <input v-model="teamForm.role" type="text" placeholder="e.g. SENIOR DEVELOPER" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase shadow-inner focus:ring-2 ring-indigo-100 transition-all">
          </div>
        </div>
        <div class="p-6 bg-slate-50 flex gap-3">
          <button @click="addMember" class="flex-1 bg-indigo-600 text-white py-3.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 transition-all active:scale-95">Confirm Assignment</button>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH TRANSAKSI KAS (KHUSUS PROJECT) -->
    <div v-if="showTxModal" class="fixed inset-0 z-200 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-[3rem] w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="sticky top-0 bg-white/80 backdrop-blur-md p-8 border-b border-slate-100 flex justify-between items-center z-20">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">Draft Transaksi Project</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pencatatan Ledger Kas Otomatis ke ID-{{ $route.params.id }}</p>
          </div>
          <button @click="showTxModal = false" class="w-10 h-10 rounded-2xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        <div class="p-8 space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipe Arus Kas</label>
              <div class="flex gap-4">
                <button @click="txForm.type = 'inflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all" :class="txForm.type === 'inflow' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'">
                  <i class="fas fa-arrow-down mr-2"></i> Income
                </button>
                <button @click="txForm.type = 'outflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all" :class="txForm.type === 'outflow' ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'">
                  <i class="fas fa-arrow-up mr-2"></i> Expense
                </button>
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tanggal Transaksi</label>
                <input v-model="txForm.date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 text-slate-700">
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Referensi</label>
                <input v-model="txForm.ref_number" type="text" placeholder="Auto / Manual" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 placeholder:text-slate-300">
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex justify-between items-center ml-1">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Akun COA (Chart of Account)</label>
              <span class="text-[8px] font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded">Otomatis Terfilter ke PT Project</span>
            </div>
            <select v-model="txForm.coa_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
              <option value="" disabled>-- Pilih Klasifikasi Akun --</option>
              <option v-for="coa in availableCOAs" :key="coa.id" :value="coa.id">[{{ coa.code }}] {{ coa.name }}</option>
            </select>
          </div>

          <div class="bg-indigo-50/50 border border-indigo-100 p-6 rounded-[2rem] space-y-6">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Nominal Transaksi (IDR)</label>
              <div class="relative">
                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-lg font-black text-indigo-300">Rp</span>
                <input v-model="txForm.amount" type="number" class="w-full bg-white border border-indigo-100 rounded-2xl pl-16 pr-5 py-4 text-xl font-black text-indigo-900 outline-none focus:ring-2 ring-indigo-200">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Metode</label>
                <select v-model="txForm.method" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                  <option value="transfer">Bank Transfer</option>
                  <option value="cash">Uang Tunai (Cash)</option>
                  <option value="ewallet">E-Wallet</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Dari Bank / Dompet</label>
                <select v-model="txForm.bank_from" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                  <option value="">-- Asal Dana --</option>
                  <option v-for="bank in availableBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }} - {{ bank.account_name }}</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Ke Bank / Dompet (Tujuan)</label>
                <select v-model="txForm.bank_to" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                  <option value="">-- Tujuan Dana --</option>
                  <option v-for="bank in availableBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }} - {{ bank.account_name }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Keterangan / Catatan</label>
              <textarea v-model="txForm.description" rows="3" placeholder="Tuliskan tujuan atau rincian transaksi..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 resize-none"></textarea>
            </div>
            
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Bukti Dokumen / Invoice</label>
              <div class="relative w-full h-[90px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer group overflow-hidden">
                <input type="file" @change="handleTxFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf,.png,.jpg,.jpeg">
                <i class="fas fa-cloud-upload-alt text-xl mb-1 group-hover:text-indigo-500" :class="txForm.attachment ? 'text-indigo-500' : ''"></i>
                <span class="text-[9px] font-black uppercase tracking-widest truncate max-w-[80%] text-center" :class="txForm.attachment ? 'text-indigo-600' : ''">
                  {{ txForm.attachment ? txForm.attachment.name : 'Klik untuk Upload Dokumen' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="sticky bottom-0 bg-white border-t border-slate-100 p-6 flex justify-end gap-4 rounded-b-[3rem] z-20">
          <button @click="showTxModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
          <button @click="handleSaveTransaction" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
            Submit Transaksi
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL TAMBAH/EDIT COA (KHUSUS ACCOUNTING) -->
    <div v-if="showCoaModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCoaModal = false"></div>
      
      <div class="bg-white rounded-[3rem] w-full max-w-lg relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ coaForm.id ? 'Edit Akun COA' : 'Buat Akun COA Baru' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pengaturan Master Ledger</p>
          </div>
          <button @click="showCoaModal = false" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all flex items-center justify-center shadow-sm">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-8 space-y-6">
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kepemilikan Akun (Entity)</label>
            <select v-model="coaForm.pt_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
              <option value="">-- Personal / General (Berlaku Semua) --</option>
              <option v-for="pt in allCompanies" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kode Akun</label>
              <input v-model="coaForm.code" type="text" placeholder="Contoh: 1-1001" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kategori Akun</label>
              <select v-model="coaForm.category" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="Asset">Asset (Harta)</option>
                <option value="Liability">Liability (Kewajiban)</option>
                <option value="Equity">Equity (Modal)</option>
                <option value="Revenue">Revenue (Pendapatan)</option>
                <option value="Expense">Expense (Beban)</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Akun (Account Name)</label>
            <input v-model="coaForm.name" type="text" placeholder="Contoh: Kas Kecil Proyek A" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
          </div>
        </div>

        <div class="p-8 border-t border-slate-100 flex justify-end gap-4 bg-slate-50/50 rounded-b-[3rem]">
          <button @click="showCoaModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
          <button @click="handleSaveCOA" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
            {{ coaForm.id ? 'Simpan Perubahan' : 'Buat Akun' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

// ==========================================
// 1. GLOBAL & CORE STATE
// ==========================================
const route = useRoute();
const subTab = ref('overview');
// const activeActivityNav = ref('tasks'); 
const activeFinanceNav = ref('overview');

const project = ref<any>({
  project_title: '',
  client_name: '',
  company_id: null,
  status: '',
  priority: '',
  package: '',
  start_date: '',
  finish_date: '',
  description: ''
});

const allCompanies = ref<any[]>([]);
const masterData = ref({
  categories: [] as any[],
  status: [] as any[],
  priority: [] as any[],
  package: [] as any[]
});

const fetchMaster = async () => {
  try {
    const res = await api.get('/get-master-data');
    masterData.value = res.data;
  } catch (e) {
    console.error("Gagal load master", e);
  }
};

const fetchDetail = async () => {
  try {
    const id = route.params.id;
    const res = await api.get(`/projects/${id}`);
    project.value = res.data;
    
    // Otomatis set filter COA ke PT project ini jika ada
    if (res.data.company_id) {
      activeAccEntity.value = res.data.company_id;
    } else {
      activeAccEntity.value = 'personal';
    }
  } catch (e) {
    console.error("Gagal ambil detail", e);
  }
};

const updateDetail = async () => {
  try {
    const id = route.params.id;
    await api.put(`/projects/detail/${id}`, {
      project_title: project.value.project_title,
      client_name: project.value.client_name,
      contract_value: project.value.contract_value,
      start_date: project.value.start_date,
      finish_date: project.value.finish_date,
      description: project.value.description,
      status: project.value.status,
      priority: project.value.priority,
      package: project.value.package,
      category_id: project.value.category_id,
      company_id: project.value.company_id,
    });
    console.log("Laporan project berhasil diperbarui secara otomatis");
  } catch (e) {
    console.error("Gagal memperbarui laporan detail", e);
  }
};

// ==========================================
// 2. UTILITIES (FORMATTING)
// ==========================================
const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (dateStr: any) => {
  if (!dateStr) return 'Recently';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const statusClass = (status: string) => {
  switch (status) {
    case 'Draft': return 'bg-slate-100 text-slate-500 border-slate-200';
    case 'Sent': return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'Done': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    default: return 'bg-slate-100 text-slate-500 border-slate-200';
  }
};

const priorityColor = (p: string) => {
  if (p === 'Urgent') return 'bg-rose-100 text-rose-600 border-rose-200';
  if (p === 'High') return 'bg-amber-100 text-amber-600 border-amber-200';
  return 'bg-blue-100 text-blue-600 border-blue-200';
};

// ==========================================
// 3. TAB: AKTIVTY (TASK MANAGEMENT)
// ==========================================
const newTaskName = ref('');
const newTaskCategory = ref('GENERAL');
const newTaskPriority = ref('Medium');
const showTaskModal = ref(false);
const activeTask = ref<any>(null);

const handleAddTask = async () => {
  if (!newTaskName.value) return;
  try {
    await api.post('/project-tasks', {
      project_id: route.params.id,
      task_name: newTaskName.value,
      task_category: newTaskCategory.value.toUpperCase(),
      priority: newTaskPriority.value
    });
    newTaskName.value = ''; 
    await fetchDetail();
  } catch (e) {
    console.error("Gagal simpan task", e);
  }
};

const openTaskDetail = (task: any) => {
  activeTask.value = { ...task };
  showTaskModal.value = true;
};

const saveTaskDetail = async () => {
  try {
    await api.put(`/project-tasks/${activeTask.value.id}`, {
      description: activeTask.value.description
    });
    showTaskModal.value = false;
    await fetchDetail(); 
    alert("Documentation Updated!");
  } catch (e) {
    console.error(e);
    alert("Gagal update dokumentasi");
  }
};

const handleToggleTask = async (task: any) => {
  try {
    await api.put(`/project-tasks/${task.id}/toggle`);
    await fetchDetail();
  } catch (e) {
    console.error("Gagal update status task", e);
  }
};

const handleDeleteTask = async (taskId: number) => {
  if (!confirm('Hapus aktivitas ini?')) return;
  try {
    await api.delete(`/project-tasks/${taskId}`);
    await fetchDetail();
  } catch (e) {
    console.error("Gagal hapus task", e);
  }
};

// ==========================================
// 4. TAB: WORKORDER
// ==========================================
const showWOModal = ref(false);
const woForm = ref({
  id: null as number | null,
  title: '',
  pic_name: '',
  budget: 0,
  description: '',
  status: 'Draft'
});

const openCreateWO = () => {
  woForm.value = { id: null, title: '', pic_name: 'SUHERY', budget: 0, description: '', status: 'Draft' };
  showWOModal.value = true;
};

const saveWO = async () => {
  try {
    const payload = { ...woForm.value, project_id: route.params.id };
    if (woForm.value.id) {
      await api.put(`/work-orders/${woForm.value.id}`, payload);
    } else {
      await api.post('/work-orders', payload);
    }
    showWOModal.value = false;
    await fetchDetail(); 
  } catch (e) {
    console.error(e);
  }
};

const editWO = (wo: any) => {
  woForm.value = { id: wo.id, title: wo.title, pic_name: wo.pic_name, budget: wo.budget, description: wo.description, status: wo.status };
  showWOModal.value = true;
};

const deleteWO = async (id: number) => {
  if (!confirm('Apakah anda yakin ingin menghapus Work Order ini?')) return;
  try {
    await api.delete(`/work-orders/${id}`);
    await fetchDetail();
    console.log("Workorder Deleted");
  } catch (e) {
    console.error("Gagal menghapus WO", e);
    alert("Gagal menghapus Work Order");
  }
};

// ==========================================
// 5. TAB: TEAMWORK
// ==========================================
const showTeamModal = ref(false);
const allUsers = ref<any[]>([]);
const teamForm = ref({
  user_id: null,
  role: ''
});

const openAddMemberModal = async () => {
  try {
    const res = await api.get('/users'); 
    allUsers.value = res.data;
    showTeamModal.value = true;
  } catch (e) {
    console.error("Gagal load users", e);
    alert("Check connection to user database");
  }
};

const addMember = async () => {
  if (!teamForm.value.user_id || !teamForm.value.role) return;
  try {
    await api.post(`/projects/${route.params.id}/team`, teamForm.value);
    showTeamModal.value = false;
    teamForm.value = { user_id: null, role: '' };
    await fetchDetail(); 
  } catch (e) {
    alert("User sudah ada di project atau terjadi error");
  }
};

const removeMember = async (userId: number) => {
  if(!confirm('Keluarkan anggota ini dari project?')) return;
  try {
    await api.delete(`/projects/${route.params.id}/team/${userId}`);
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 6. TAB: PRODUKS (DELIVERABLES)
// ==========================================
const prodForm = ref({
  title: '',
  type: 'Link',
  version: '1.0.0',
  content: ''
});

const handleSaveProduction = async () => {
  if (!prodForm.value.title || !prodForm.value.content) return alert("Title and Content are required!");
  try {
    await api.post('/project-productions', {
      project_id: route.params.id,
      ...prodForm.value
    });
    prodForm.value = { title: '', type: 'Link', version: '1.0.0', content: '' };
    await fetchDetail();
    alert("Production data saved!");
  } catch (e) {
    console.error(e);
  }
};

const handleDeleteProduction = async (id: number) => {
  if (!confirm('Delete this production output?')) return;
  try {
    await api.delete(`/project-productions/${id}`);
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 7. TAB: DOCUMENT
// ==========================================
const docForm = ref({ title: '', file: null as File | null });
const loadingDoc = ref(false);

const handleFileChange = (e: any) => {
  docForm.value.file = e.target.files[0];
};

const uploadDoc = async () => {
  if (!docForm.value.title || !docForm.value.file) {
    alert("Harap isi Judul dan Pilih File!");
    return;
  }
  loadingDoc.value = true;
  const formData = new FormData();
  formData.append('project_id', route.params.id as string);
  formData.append('title', docForm.value.title);
  formData.append('file', docForm.value.file);

  try {
    await api.post('/project-documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    docForm.value = { title: '', file: null };
    const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement;
    if (fileInput) fileInput.value = ''; 
    await fetchDetail();
    console.log("Document Uploaded!");
  } catch (e) {
    console.error("Gagal upload", e);
    alert("Gagal mengunggah dokumen");
  } finally {
    loadingDoc.value = false;
  }
};

const deleteDoc = async (id: number) => {
  if (!confirm('Hapus dokumen ini secara permanen?')) return;
  try {
    await api.delete(`/project-documents/${id}`);
    await fetchDetail(); 
    console.log("Document Deleted");
  } catch (e) {
    console.error("Gagal hapus doc", e);
    alert("Gagal menghapus dokumen");
  }
};

// ==========================================
// 8. TAB: SUPPORT (CRM/TICKETING)
// ==========================================
const supportForm = ref({ subject: '', priority: 'Medium', message: '' });

const handleSaveSupport = async () => {
  if (!supportForm.value.subject || !supportForm.value.message) return alert("Please fill all fields");
  try {
    await api.post('/project-supports', {
      project_id: route.params.id,
      ...supportForm.value
    });
    supportForm.value = { subject: '', priority: 'Medium', message: '' };
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

const updateTicketStatus = async (id: number, event: any) => {
  try {
    await api.put(`/project-supports/${id}/status`, { status: event.target.value });
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 9. TAB: MARKETING
// ==========================================
const marketForm = ref({
  title: '',
  type: 'Upselling',
  next_follow_up: '',
  notes: '',
  budget_estimate: 0
});

const handleSaveMarketing = async () => {
  if (!marketForm.value.title) return alert("Opportunity Title harus diisi!");
  try {
    await api.post('/project-marketings', {
      project_id: route.params.id,
      ...marketForm.value
    });
    marketForm.value = { title: '', type: 'Upselling', next_follow_up: '', notes: '', budget_estimate: 0 };
    await fetchDetail();
    console.log("Marketing Opportunity Saved!");
  } catch (e) {
    console.error("Gagal simpan marketing", e);
    alert("Gagal menyimpan data marketing");
  }
};

const handleDeleteMarketing = async (id: number) => {
  if (!confirm('Hapus data marketing ini?')) return;
  try {
    await api.delete(`/project-marketings/${id}`);
    await fetchDetail(); 
    console.log("Marketing Deleted");
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 10. TAB: PURCHASING
// ==========================================
const purchaseForm = ref({
  item_name: '',
  vendor_name: '',
  amount: 0,
  quantity: 1,
  purchase_date: '',
  notes: ''
});

const handleSavePurchase = async () => {
  if (!purchaseForm.value.item_name || purchaseForm.value.amount <= 0) return alert("Isi Item dan Harga!");
  try {
    await api.post('/project-purchasings', {
      project_id: route.params.id,
      ...purchaseForm.value
    });
    purchaseForm.value = { item_name: '', vendor_name: '', amount: 0, quantity: 1, purchase_date: '', notes: '' };
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

const handleDeletePurchase = async (id: number) => {
  if (!confirm('Hapus catatan belanja?')) return;
  try {
    await api.delete(`/project-purchasings/${id}`);
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 11. TAB: FINANCIAL (CASHBOOK & INVOICE)
// ==========================================
const dbCOAs = ref<any[]>([]);
const dbBanks = ref<any[]>([]);
const projectTransactions = ref<any[]>([]);
const showTxModal = ref(false);

const txForm = ref({
  type: 'outflow',
  date: new Date().toISOString().split('T')[0],
  ref_number: '',
  coa_id: '',
  method: 'transfer',
  bank_from: '',
  bank_to: '',
  amount: 0,
  description: '',
  attachment: null as File | null
});

// Otomatis memfilter COA & Bank menyesuaikan PT yang menaungi Project ini
const availableCOAs = computed(() => {
  if (!project.value?.company_id) return dbCOAs.value.filter(c => !c.pt_id);
  return dbCOAs.value.filter(c => !c.pt_id || c.pt_id === project.value.company_id);
});
const availableBanks = computed(() => {
  if (!project.value?.company_id) return dbBanks.value.filter(b => !b.pt_id);
  return dbBanks.value.filter(b => !b.pt_id || b.pt_id === project.value.company_id);
});

const fetchMasterFinance = async () => {
  try {
    const [resCOA, resBank] = await Promise.all([
      api.get('/accounting/coas'),
      api.get('/finance/banks')
    ]);
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbBanks.value = resBank.data.data || resBank.data;
  } catch (e) { console.error("Gagal load Master Finance", e); }
};

const fetchProjectTransactions = async () => {
  try {
    const res = await api.get('/finance/transactions', { 
      params: { project_id: route.params.id, status: 'all', type: 'all' } 
    });
    projectTransactions.value = res.data;
  } catch (error) { console.error("Gagal load Data Transaksi", error); }
};

const handleTxFileChange = (e: any) => {
  if (e.target.files[0]) txForm.value.attachment = e.target.files[0];
};

const handleSaveTransaction = async () => {
  if (txForm.value.amount <= 0 || !txForm.value.coa_id) return alert('Nominal dan COA wajib diisi!');
  try {
    const formData = new FormData();
    formData.append('type', String(txForm.value.type));
    formData.append('date', String(txForm.value.date));
    formData.append('amount', String(txForm.value.amount));
    formData.append('coa_id', String(txForm.value.coa_id));
    formData.append('method', String(txForm.value.method));
    formData.append('description', String(txForm.value.description));
    formData.append('project_id', String(route.params.id)); // Otomatis dilock ke project ini
    
    if (txForm.value.ref_number) formData.append('ref_number', String(txForm.value.ref_number));
    if (txForm.value.bank_from) formData.append('bank_from', String(txForm.value.bank_from));
    if (txForm.value.bank_to) formData.append('bank_to', String(txForm.value.bank_to));
    if (txForm.value.attachment) formData.append('attachment', txForm.value.attachment);

    await api.post('/finance/transactions', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    alert("Transaksi kas berhasil dicatat!");
    showTxModal.value = false;
    await fetchProjectTransactions(); // Refresh tabel transaksi
    
    // Reset Form
    txForm.value.amount = 0; txForm.value.description = ''; txForm.value.attachment = null;
  } catch (error: any) {
    console.error(error); alert("Gagal menyimpan transaksi kas.");
  }
};

const invForm = ref({ title: '', amount: 0, due_date: '' });

const calculateTotalExpenses = () => {
  const woTotal = project.value?.work_orders?.reduce((t: any, wo: any) => t + parseFloat(wo.budget || 0), 0) || 0;
  const purchaseTotal = project.value?.purchasings?.reduce((t: any, p: any) => t + parseFloat(p.total_price || 0), 0) || 0;
  return woTotal + purchaseTotal;
};

const calculateMargin = () => {
  const revenue = parseFloat(project.value?.contract_value || 0);
  const expenses = calculateTotalExpenses();
  if (revenue === 0) return 0;
  return ((revenue - expenses) / revenue * 100);
};

const calculateTotalInvoiced = (status: string) => {
  if (!project.value?.invoices) return 0;
  return project.value.invoices
    .filter((inv: any) => inv.status === status)
    .reduce((t: any, inv: any) => t + parseFloat(inv.amount || 0), 0);
};

const handleSaveInvoice = async () => {
  if (!invForm.value.title || invForm.value.amount <= 0) {
    alert("Harap isi Judul dan Nominal Invoice!");
    return;
  }
  try {
    await api.post('/project-invoices', {
      project_id: route.params.id,
      ...invForm.value
    });
    invForm.value = { title: '', amount: 0, due_date: '' };
    await fetchDetail(); 
    alert("Invoice berhasil dibuat!");
  } catch (e) {
    console.error(e);
  }
};

const updateInvStatus = async (id: number, status: string) => {
  try {
    await api.put(`/project-invoices/${id}/status`, { status });
    await fetchDetail();
  } catch (e) {
    console.error(e);
  }
};

// ==========================================
// 12. TAB: ACCOUNTING (COA MASTER)
// ==========================================
const activeAccEntity = ref('all');
const activeAccCategory = ref('all');
const searchCOA = ref('');

const coaCategories = [
  { id: 'all', label: 'Semua Kategori', icon: 'fa-layer-group', color: 'text-slate-500' },
  { id: 'Asset', label: 'Harta (Assets)', icon: 'fa-wallet', color: 'text-emerald-500' },
  { id: 'Liability', label: 'Kewajiban (Liabilities)', icon: 'fa-hand-holding-usd', color: 'text-rose-500' },
  { id: 'Equity', label: 'Modal (Equity)', icon: 'fa-scale-balanced', color: 'text-indigo-500' },
  { id: 'Revenue', label: 'Pendapatan (Revenue)', icon: 'fa-arrow-trend-up', color: 'text-emerald-400' },
  { id: 'Expense', label: 'Beban (Expenses)', icon: 'fa-arrow-trend-down', color: 'text-rose-400' },
];

const filteredCOAs = computed(() => {
  let data = [...dbCOAs.value];
  if (activeAccEntity.value !== 'all') {
    if (activeAccEntity.value === 'personal') data = data.filter(c => c.pt_id === null || c.pt_id === '');
    else data = data.filter(c => c.pt_id === activeAccEntity.value);
  }
  if (activeAccCategory.value !== 'all') data = data.filter(c => c.category === activeAccCategory.value);
  if (searchCOA.value) {
    const q = searchCOA.value.toLowerCase();
    data = data.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q));
  }
  return data;
});

const showCoaModal = ref(false);
const coaForm = ref({
  id: null as number | null,
  pt_id: '' as string | null,
  code: '',
  name: '',
  category: 'Asset'
});

const openCoaModal = (coa: any = null) => {
  if (coa) {
    coaForm.value = { ...coa, pt_id: coa.pt_id || '' };
  } else {
    // Default form ke entitas yang sedang difilter
    const defaultPt = (activeAccEntity.value !== 'all' && activeAccEntity.value !== 'personal') ? activeAccEntity.value : '';
    coaForm.value = { id: null, pt_id: defaultPt as string, code: '', name: '', category: 'Asset' };
  }
  showCoaModal.value = true;
};

const handleSaveCOA = async () => {
  try {
    const payload: Record<string, any> = { ...coaForm.value };
    if (!payload.pt_id) payload.pt_id = null;

    if (payload.id) {
      await api.put(`/accounting/coas/${payload.id}`, payload);
      alert("Akun COA berhasil diperbarui!");
    } else {
      await api.post('/accounting/coas', payload);
      alert("Akun COA baru berhasil dibuat!");
    }
    
    showCoaModal.value = false;
    await fetchMasterFinance(); // Update state dbCOAs
  } catch (error) {
    console.error("Gagal simpan COA", error);
  }
};

const handleDeleteCOA = async (id: number) => {
  if (!confirm("Hapus akun COA ini?")) return;
  try {
    await api.delete(`/accounting/coas/${id}`);
    await fetchMasterFinance(); 
  } catch (error) {
    console.error("Gagal hapus COA", error);
  }
};

// ==========================================
// 13. LIFECYCLE HOOKS
// ==========================================
onMounted(async () => {
  await fetchMaster();
  await fetchDetail();
  await fetchMasterFinance(); // Load COA dan Bank
  await fetchProjectTransactions(); // Load riwayat kas project
  
  const resComp = await api.get('/companies');
  allCompanies.value = resComp.data;
});
</script>