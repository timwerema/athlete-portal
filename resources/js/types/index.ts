export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
}

export interface SharedData {
    name: string;
    auth: { user: User | null };
    flash: { status: string | null };
    unread: number;
    [key: string]: unknown;
}

export interface ChecklistItem {
    key: string;
    label: string;
    section: string;
    done: boolean;
    optional: boolean;
}

export interface Completeness {
    done: number;
    total: number;
    items: ChecklistItem[];
}

export interface ViewSummary {
    total: number;
    last_7_days: number;
    last_viewed_on: string | null;
}

export interface StatField {
    key: string;
    label: string;
    short: string;
    type?: 'int' | 'decimal';
    group?: string;
    max?: number;
}

export type Stats = Record<string, number | null | undefined>;

export interface Team {
    id: number;
    kind: string;
    name: string;
    detail: string | null;
    start_year: number | null;
    end_year: number | null;
}

export interface SeasonStat {
    id: number;
    season: string;
    team: string;
    competition: string | null;
    stats: Stats;
}

export interface Honour {
    id: number;
    title: string;
    year: number | null;
}

export interface VideoItem {
    id: number;
    kind: string;
    title: string;
    url: string;
    provider: string;
    label: string;
    embed_url: string | null;
}

export interface Reference {
    id: number;
    name: string;
    role: string;
    organization: string | null;
    contact: string;
}

export interface LookingFor {
    levels: string[];
    regions: string[];
    intended_major: string | null;
    timeline: string | null;
}

export interface OwnerProfile {
    display_name: string | null;
    pronouns: string | null;
    has_photo: boolean;
    photo_version: string | null;
    height_cm: number | null;
    current_school: string | null;
    division: string | null;
    conference: string | null;
    class_year: string | null;
    eligibility_years_left: number | null;
    portal_entered_on: string | null;
    sport: string;
    sport_data: { positions: string[]; preferred_foot: string | null };
    major: string | null;
    gpa: string | null;
    gpa_scale: string | null;
    looking_for: LookingFor;
    statement: string | null;
    hidden_sections: string[];
    teams: Team[];
    season_stats: SeasonStat[];
    honours: Honour[];
    videos: VideoItem[];
    references: Reference[];
}

export interface ProfileOptions {
    positions: Record<string, string>;
    feet: Record<string, string>;
    statFields: StatField[];
    divisions: string[];
    classYears: string[];
    regions: string[];
    teamKinds: Record<string, string>;
    videoKinds: Record<string, string>;
    photoMaxKilobytes: number;
}

/** What a coach with the link is sent. Built field by field on the server. */
export interface CoachProfile {
    is_demo: boolean;
    sport: string;
    display_name: string | null;
    pronouns: string | null;
    photo_url: string | null;
    positions: { code: string; label: string }[];
    preferred_foot: string | null;
    height_cm: number | null;
    current_school: string | null;
    division: string | null;
    conference: string | null;
    class_year: string | null;
    eligibility_years_left: number | null;
    portal_entered_on: string | null;
    major: string | null;
    gpa: { value: string; scale: string | null } | null;
    statement: string | null;
    looking_for: LookingFor | null;
    teams: Omit<Team, 'id'>[];
    stat_fields: StatField[];
    season_stats: Omit<SeasonStat, 'id'>[];
    honours: Omit<Honour, 'id'>[];
    videos: Omit<VideoItem, 'id'>[];
    references: Omit<Reference, 'id'>[];
    updated_at: string | null;
}
