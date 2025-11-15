'use client' // 👈 A LINHA MÁGICA!

import { AuthProvider } from '@/context/AuthContext';
import { ReactNode } from 'react';

export function Providers({ children }: { children: ReactNode }) {
  return (
    <AuthProvider>
      {children}
    </AuthProvider>
  );
}