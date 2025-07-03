import Navbar from "./components/shared/navBar";
import Provider from "./components/ui/provider";
import "./globals.css";

export const metadata = {
  title: "Greenhouse Games",
  description: "A platform for educational games",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>
        <Provider>
          <Navbar />
          {children}
        </Provider>
      </body>
    </html>
  );
}
