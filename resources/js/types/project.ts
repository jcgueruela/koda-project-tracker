export type ProjectStatus =
    'planning' | 'in_progress' | 'on_hold' | 'completed';
export type ProjectPriority = 'low' | 'medium' | 'high';

export interface Project {
    id: number;
    name: string;
    client_name: string;
    description: string | null;
    status: ProjectStatus;
    priority: ProjectPriority;
    start_date: string;
    due_date: string;
    created_at: string;
    updated_at: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedProjects {
    data: Project[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}

export const PROJECT_STATUSES: ProjectStatus[] = [
    'planning',
    'in_progress',
    'on_hold',
    'completed',
];
export const PROJECT_PRIORITIES: ProjectPriority[] = ['low', 'medium', 'high'];

export const PROJECT_STATUS_LABELS: Record<ProjectStatus, string> = {
    planning: 'Planning',
    in_progress: 'In Progress',
    on_hold: 'On Hold',
    completed: 'Completed',
};

export const PROJECT_PRIORITY_LABELS: Record<ProjectPriority, string> = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};
