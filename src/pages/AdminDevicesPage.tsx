import { IonContent, IonHeader, IonPage, IonTitle, IonToolbar } from '@ionic/react';

const AdminDevicesPage: React.FC = () => (
    <IonPage>
        <IonHeader>
            <IonToolbar>
                <IonTitle>Admin · Devices</IonTitle>
            </IonToolbar>
        </IonHeader>
        <IonContent className="ion-padding">
            <p>Scaffold slice-016 — admin devices stub.</p>
        </IonContent>
    </IonPage>
);

export default AdminDevicesPage;
