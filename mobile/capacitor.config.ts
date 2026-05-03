import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'app.kalibrium.tecnico',
    appName: 'Kalibrium Técnico',
    webDir: 'dist',
    plugins: {
        CapacitorSQLite: {
            iosDatabaseLocation: 'Library/CapacitorDatabase',
            iosIsEncryption: true,
            iosKeychainPrefix: 'kalibrium',
            iosBiometric: {
                biometricAuth: false,
                biometricTitle: 'Biometric login for capacitor sqlite',
            },
            androidIsEncryption: true,
            androidBiometric: {
                biometricAuth: false,
                biometricTitle: 'Biometric login for capacitor sqlite',
            },
        },
    },
};

export default config;
