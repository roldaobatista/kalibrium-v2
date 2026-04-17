import {
    IonCol,
    IonContent,
    IonGrid,
    IonHeader,
    IonPage,
    IonRow,
    IonTitle,
    IonToolbar,
} from '@ionic/react';

const HomePage: React.FC = () => (
    <IonPage>
        <IonHeader>
            <IonToolbar>
                <IonTitle>Home</IonTitle>
            </IonToolbar>
        </IonHeader>
        <IonContent className="ion-padding">
            <IonGrid>
                <IonRow>
                    <IonCol size="12" sizeMd="6">
                        <h2>Bem-vindo</h2>
                        <p>Kalibrium scaffold — slice-016.</p>
                    </IonCol>
                    <IonCol size="12" sizeMd="6">
                        <p>Layout adaptativo via Ionic Grid (AC-006).</p>
                    </IonCol>
                </IonRow>
            </IonGrid>
        </IonContent>
    </IonPage>
);

export default HomePage;
