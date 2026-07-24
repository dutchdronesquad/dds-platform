export type ContactDeliveryStatus =
    'failed' | 'not_configured' | 'pending' | 'sent';

export type ContactSubmissionRow = {
    createdAt: string;
    deliveryStatus: ContactDeliveryStatus;
    deliveryStatusLabel: string;
    email: string;
    id: number;
    messageExcerpt: string;
    name: string;
    topicLabel: string;
};

export type ContactSubmissionDetail = {
    consentedAt: string;
    createdAt: string;
    deliveredAt: string | null;
    deliveryAttemptedAt: string | null;
    deliveryError: string | null;
    deliveryStatus: ContactDeliveryStatus;
    deliveryStatusLabel: string;
    email: string;
    id: number;
    message: string;
    name: string;
    sourceContext: string | null;
    topic: string;
    topicLabel: string;
};
