import { ReactNode } from "react";
import RootLayout from "../(student)/layout"; // importa o layout que já tem estilo administrativo

export default function RequestLayout({ children }: { children: ReactNode }) {
  return <RootLayout>{children}</RootLayout>;
}
