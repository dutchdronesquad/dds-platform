export type UserRecord = {
    id: number;
    name: string;
    email: string;
    locale: string;
    roles: string[];
    isActive: boolean;
    emailVerifiedAt: string | null;
    lastActiveAt: string | null;
    createdAt: string;
};

export type EditableUser = UserRecord & {
    capabilities: {
        block: boolean;
        delete: boolean;
        unblock: boolean;
    };
    isCurrentUser: boolean;
    isSoleActiveAdmin: boolean;
};

export type Option = {
    value: string;
    label: string;
};

export type RoleOption = Option & {
    description: string;
};
