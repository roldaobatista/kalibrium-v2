import React from 'react';
import { IonContent, IonPage } from '@ionic/react';
import './Blocked.css';

const Blocked: React.FC = () => {
    return (
        <IonPage className="kb-blocked-page">
            <IonContent>
                <div className="kb-blocked-content">
                    <div className="kb-blocked-icon" aria-hidden="true">
                        &#9888;
                    </div>
                    <h1 className="kb-blocked-title">Celular bloqueado</h1>
                    <p className="kb-blocked-message">
                        Este celular foi bloqueado pelo seu laboratório. Entre em contato com o
                        gerente.
                    </p>
                </div>
            </IonContent>
        </IonPage>
    );
};

export default Blocked;
