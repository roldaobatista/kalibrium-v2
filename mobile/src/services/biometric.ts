import { NativeBiometric } from 'capacitor-native-biometric';

/**
 * Chave usada como "servidor" no cofre de credenciais do dispositivo.
 * Deve ser única por app — não alterar depois do primeiro deploy.
 */
const CREDENTIAL_SERVER = 'kalibrium';

/**
 * Verifica se o dispositivo tem biometria disponível E cadastrada.
 * Em navegador desktop (sem plugin nativo), retorna false silenciosamente.
 */
export async function isAvailable(): Promise<boolean> {
    try {
        const result = await NativeBiometric.isAvailable();
        return result.isAvailable;
    } catch {
        return false;
    }
}

/**
 * Verifica se há credenciais biométricas salvas para este app.
 * Retorna false se não houver ou se o plugin não estiver disponível.
 */
export async function hasEnrolled(): Promise<boolean> {
    try {
        const creds = await NativeBiometric.getCredentials({ server: CREDENTIAL_SERVER });
        return creds.username !== '' && creds.password !== '';
    } catch {
        return false;
    }
}

/**
 * Salva token + dados do usuário no cofre seguro do dispositivo (Keychain/Keystore).
 * NÃO usa localStorage — as credenciais ficam protegidas pelo SO.
 *
 * @param token  Token de autenticação retornado pelo backend
 * @param user   Objeto com dados do usuário (será serializado como JSON)
 */
export async function enroll(token: string, user: object): Promise<void> {
    await NativeBiometric.setCredentials({
        username: JSON.stringify(user),
        password: token,
        server: CREDENTIAL_SERVER,
    });
}

/**
 * Solicita autenticação biométrica ao usuário.
 * Se bem-sucedida, retorna as credenciais salvas.
 * Se cancelada ou falhar, retorna null sem lançar erro.
 *
 * @returns { token, user } se autenticado, null se cancelado/falhou
 */
export async function authenticate(): Promise<{ token: string; user: object } | null> {
    try {
        await NativeBiometric.verifyIdentity({
            reason: 'Confirme sua identidade para entrar no Kalibrium',
            title: 'Entrar com biometria',
            negativeButtonText: 'Usar senha',
        });

        const creds = await NativeBiometric.getCredentials({ server: CREDENTIAL_SERVER });

        const user = JSON.parse(creds.username) as object;
        const token = creds.password;

        return { token, user };
    } catch {
        // Usuário cancelou, falhou ou plugin indisponível — caminho normal
        return null;
    }
}

/**
 * Apaga as credenciais biométricas salvas (chamado no logout).
 * No próximo login o usuário terá que digitar email/senha,
 * e o ciclo de cadastro biométrico recomeça.
 */
export async function clear(): Promise<void> {
    try {
        await NativeBiometric.deleteCredentials({ server: CREDENTIAL_SERVER });
    } catch {
        // Sem credenciais salvas ou plugin indisponível — nada a fazer
    }
}
