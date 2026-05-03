import React from 'react';

interface AvatarProps {
    name: string;
    size?: number;
}

const AVATAR_COLORS = [
    '#2563eb', // kb-primary-600
    '#16a34a', // kb-success-600
    '#d97706', // kb-warning-600
    '#7c3aed', // roxo
    '#0891b2', // ciano
];

function hashName(name: string): number {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return Math.abs(hash);
}

const Avatar: React.FC<AvatarProps> = ({ name, size = 40 }) => {
    const initial = name.trim().charAt(0).toUpperCase() || '?';
    const bg = AVATAR_COLORS[hashName(name) % AVATAR_COLORS.length];
    const fontSize = Math.round(size * 0.42);

    return (
        <div
            aria-label={`Avatar de ${name}`}
            style={{
                width: size,
                height: size,
                borderRadius: '50%',
                background: bg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: '#ffffff',
                fontFamily: 'var(--kb-font-sans)',
                fontSize,
                fontWeight: 700,
                flexShrink: 0,
                userSelect: 'none',
            }}
        >
            {initial}
        </div>
    );
};

export default Avatar;
