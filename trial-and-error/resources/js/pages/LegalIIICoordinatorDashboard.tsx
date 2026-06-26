import { usePage } from '@inertiajs/react';
import { StageDashboard } from '@/components/StageDashboard';

export default function LegalIIICoordinatorDashboard() {
    const props = usePage().props as any;

    return (
        <StageDashboard
            stage={props.stage}
            stageName={props.stageName}
            stageHandler={props.stageHandler}
            nextStage={props.nextStage}
            prevStage={props.prevStage}
            agreementsAtStage={props.agreementsAtStage}
            stats={props.stats}
            analytics={props.analytics}
            expiringSoon={props.expiringSoon}
            expired={props.expired}
            recentActivities={props.recentActivities}
            notifications={props.notifications}
            unreadNotifications={props.unreadNotifications}
        />
    );
}