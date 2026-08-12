<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import {
    PROJECT_PRIORITIES,
    PROJECT_PRIORITY_LABELS,
    PROJECT_STATUSES,
    PROJECT_STATUS_LABELS,
    type PaginatedProjects,
    type Project,
    type ProjectPriority,
    type ProjectStatus,
} from '@/types/project';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: '',
            },
        ],
    },
});

type SortField =
    | 'client_name'
    | 'name'
    | 'status'
    | 'priority'
    | 'start_date'
    | 'due_date'
    | 'created_at';

const filters = reactive({
    search: '',
    status: '' as '' | ProjectStatus,
    priority: '' as '' | ProjectPriority,
    sort: 'created_at' as SortField,
    direction: 'desc' as 'asc' | 'desc',
    page: 1,
});

const projects = ref<Project[]>([]);
const meta = ref<PaginatedProjects['meta'] | null>(null);
const initialLoad = ref(true);

const list = useHttp({});

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

function readFiltersFromUrl() {
    const params = new URLSearchParams(window.location.search);

    filters.search = params.get('search') ?? '';
    filters.status = (params.get('status') as ProjectStatus) ?? '';
    filters.priority = (params.get('priority') as ProjectPriority) ?? '';
    filters.sort = (params.get('sort') as SortField) ?? 'created_at';
    filters.direction = params.get('direction') === 'asc' ? 'asc' : 'desc';
    filters.page = Number(params.get('page') ?? 1) || 1;
}

function buildQuery(): string {
    const params = new URLSearchParams();

    if (filters.search) params.set('search', filters.search);
    if (filters.status) params.set('status', filters.status);
    if (filters.priority) params.set('priority', filters.priority);
    if (filters.sort !== 'created_at') params.set('sort', filters.sort);
    if (filters.direction !== 'desc')
        params.set('direction', filters.direction);
    if (filters.page > 1) params.set('page', String(filters.page));

    return params.toString();
}

function syncUrl() {
    const query = buildQuery();
    const url = query
        ? `${window.location.pathname}?${query}`
        : window.location.pathname;
    window.history.replaceState({}, '', url);
}

function extractPaginated(response: unknown): PaginatedProjects | null {
    const candidates = [
        (response as any)?.data?.data,
        (response as any)?.data,
        response,
    ];

    for (const candidate of candidates) {
        if (candidate && Array.isArray(candidate.data) && candidate.meta) {
            return candidate as PaginatedProjects;
        }
    }

    return null;
}

function fetchProjects() {
    const query = buildQuery();

    list.get(`/api/projects${query ? `?${query}` : ''}`, {
        onSuccess: (response) => {
            const body = extractPaginated(response);

            projects.value = body?.data ?? [];
            meta.value = body?.meta ?? null;
        },
        onFinish: () => {
            initialLoad.value = false;
        },
    });
}

function applyFilters() {
    syncUrl();
    fetchProjects();
}

function onFilterChange() {
    filters.page = 1;
    applyFilters();
}

function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(onFilterChange, 350);
}

const statusValue = computed({
    get: () => filters.status || 'all',
    set: (value: string) => {
        filters.status = value === 'all' ? '' : (value as ProjectStatus);
        onFilterChange();
    },
});

const priorityValue = computed({
    get: () => filters.priority || 'all',
    set: (value: string) => {
        filters.priority = value === 'all' ? '' : (value as ProjectPriority);
        onFilterChange();
    },
});

function sortBy(field: SortField) {
    if (filters.sort === field) {
        filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filters.sort = field;
        filters.direction = 'asc';
    }
    onFilterChange();
}

function sortIcon(field: SortField) {
    if (filters.sort !== field) return '';
    return filters.direction === 'asc' ? '▲' : '▼';
}

function goToPage(page: number) {
    if (!meta.value) return;
    if (page < 1 || page > meta.value.last_page) return;
    filters.page = page;
    applyFilters();
}

function clearFilters() {
    filters.search = '';
    filters.status = '';
    filters.priority = '';
    filters.sort = 'created_at';
    filters.direction = 'desc';
    filters.page = 1;
    applyFilters();
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

const statusVariant: Record<ProjectStatus, string> = {
    planning:
        'bg-slate-100 text-slate-700 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300',
    in_progress:
        'bg-blue-100 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300',
    on_hold:
        'bg-amber-100 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300',
    completed:
        'bg-emerald-100 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 dark:text-emerald-300',
};

const priorityVariant: Record<ProjectPriority, string> = {
    low: 'bg-slate-100 text-slate-600 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300',
    medium: 'bg-orange-100 text-orange-700 hover:bg-orange-100 dark:bg-orange-900/40 dark:text-orange-300',
    high: 'bg-red-100 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300',
};

// ---- Create / edit dialog ----

const showModal = ref(false);
const editing = ref<Project | null>(null);

const form = useHttp({
    client_name: '',
    name: '',
    description: '',
    status: 'planning' as ProjectStatus,
    priority: 'medium' as ProjectPriority,
    start_date: '',
    due_date: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(project: Project) {
    editing.value = project;
    form.client_name = project.client_name;
    form.name = project.name;
    form.description = project.description ?? '';
    form.status = project.status;
    form.priority = project.priority;
    form.start_date = project.start_date.slice(0, 10);
    form.due_date = project.due_date.slice(0, 10);
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    const options = {
        onSuccess: () => {
            showModal.value = false;
            editing.value = null;
            form.reset();
            fetchProjects();
        },
    };

    if (editing.value) {
        form.put(`/api/projects/${editing.value.id}`, options);
    } else {
        form.post('/api/projects', options);
    }
}

// ---- Delete confirmation ----

const deletingProject = ref<Project | null>(null);
const deleteDialogOpen = ref(false);
const deleteHttp = useHttp({});

function confirmDelete(project: Project) {
    deletingProject.value = project;
    deleteDialogOpen.value = true;
}

function performDelete() {
    const project = deletingProject.value;
    if (!project) return;

    deleteHttp.delete(`/api/projects/${project.id}`, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            deletingProject.value = null;

            if (projects.value.length === 1 && filters.page > 1) {
                filters.page -= 1;
            }

            fetchProjects();
        },
    });
}

const sortableColumns: [SortField, string][] = [
    ['name', 'Project'],
    ['client_name', 'Client'],
    ['status', 'Status'],
    ['priority', 'Priority'],
    ['start_date', 'Start Date'],
    ['due_date', 'Due Date'],
];

onMounted(() => {
    readFiltersFromUrl();
    fetchProjects();
});
</script>

<template>
    <Head title="Projects" />

    <div class="px-4 py-8 sm:px-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Client Projects
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Track progress and priority across every client engagement.
                </p>
            </div>

            <Button @click="openCreate">+ New Project</Button>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Input
                v-model="filters.search"
                type="search"
                placeholder="Search by client or project name..."
                class="w-full max-w-xs"
                @input="onSearchInput"
            />

            <Select v-model="statusValue">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="status in PROJECT_STATUSES"
                        :key="status"
                        :value="status"
                    >
                        {{ PROJECT_STATUS_LABELS[status] }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="priorityValue">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="All priorities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All priorities</SelectItem>
                    <SelectItem
                        v-for="priority in PROJECT_PRIORITIES"
                        :key="priority"
                        :value="priority"
                    >
                        {{ PROJECT_PRIORITY_LABELS[priority] }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="filters.search || filters.status || filters.priority"
                variant="link"
                class="text-muted-foreground"
                @click="clearFilters"
            >
                Clear filters
            </Button>

            <span v-if="list.processing" class="text-sm text-muted-foreground"
                >Loading...</span
            >
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="col in sortableColumns"
                            :key="col[0]"
                            class="cursor-pointer select-none"
                            @click="sortBy(col[0])"
                        >
                            {{ col[1] }}
                            <span class="text-muted-foreground">{{
                                sortIcon(col[0])
                            }}</span>
                        </TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!initialLoad && projects.length === 0">
                        <TableCell
                            colspan="7"
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            No projects match your filters.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="project in projects" :key="project.id">
                        <TableCell class="font-medium">{{
                            project.name
                        }}</TableCell>
                        <TableCell>{{ project.client_name }}</TableCell>
                        <TableCell>
                            <Badge
                                :class="statusVariant[project.status]"
                                variant="secondary"
                            >
                                {{ PROJECT_STATUS_LABELS[project.status] }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :class="priorityVariant[project.priority]"
                                variant="secondary"
                            >
                                {{ PROJECT_PRIORITY_LABELS[project.priority] }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{
                            formatDate(project.start_date)
                        }}</TableCell>
                        <TableCell class="text-muted-foreground">{{
                            formatDate(project.due_date)
                        }}</TableCell>
                        <TableCell class="text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(project)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-red-600 hover:text-red-700"
                                @click="confirmDelete(project)"
                            >
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div
            v-if="meta && meta.total > 0"
            class="mt-4 flex items-center justify-between text-sm text-muted-foreground"
        >
            <p>Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Previous
                </Button>
                <span
                    >Page {{ meta.current_page }} of {{ meta.last_page }}</span
                >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>

    <!-- Create / Edit dialog -->
    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    editing ? 'Edit Project' : 'New Project'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        editing
                            ? 'Update the details for this engagement.'
                            : 'Add a new client engagement to track.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="client_name">Client Name</Label>
                        <Input
                            id="client_name"
                            v-model="form.client_name"
                            type="text"
                        />
                        <p
                            v-if="form.errors.client_name"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.client_name }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="name">Project Name</Label>
                        <Input id="name" v-model="form.name" type="text" />
                        <p v-if="form.errors.name" class="text-xs text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                    />
                    <p
                        v-if="form.errors.description"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in PROJECT_STATUSES"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ PROJECT_STATUS_LABELS[status] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.status"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="priority in PROJECT_PRIORITIES"
                                    :key="priority"
                                    :value="priority"
                                >
                                    {{ PROJECT_PRIORITY_LABELS[priority] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.priority"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.priority }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="start_date">Start Date</Label>
                        <Input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                        />
                        <p
                            v-if="form.errors.start_date"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.start_date }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="due_date">Due Date</Label>
                        <Input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                        />
                        <p
                            v-if="form.errors.due_date"
                            class="text-xs text-red-600"
                        >
                            {{ form.errors.due_date }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="closeModal"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Project' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Delete confirmation -->
    <AlertDialog v-model:open="deleteDialogOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete Project</AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to delete
                    <strong>{{ deletingProject?.name }}</strong> for
                    {{ deletingProject?.client_name }}? This cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-red-600 hover:bg-red-700"
                    :disabled="deleteHttp.processing"
                    @click.prevent="performDelete"
                >
                    {{ deleteHttp.processing ? 'Deleting...' : 'Delete' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
