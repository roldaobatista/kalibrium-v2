import { IonContent, IonHeader, IonPage, IonTitle, IonToolbar } from '@ionic/react';

const LoginPage: React.FC = () => (
    <IonPage>
        <IonHeader>
            <IonToolbar>
                <IonTitle>Login</IonTitle>
            </IonToolbar>
        </IonHeader>
        <IonContent className="ion-padding">
            <h1>Kalibrium</h1>
            <p>Scaffold slice-016 — login stub.</p>
        </IonContent>
    </IonPage>
);

export default LoginPage;
