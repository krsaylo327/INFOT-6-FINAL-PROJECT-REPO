export default function AuthCardLayout({ children, title, description }: any) {
    return (
        <div className="flex min-h-screen w-full items-center justify-center bg-gradient-to-br from-red-900 via-red-800 to-red-700 p-6">
            {/* WIDE CARD */}
            <div className="grid w-[90vw] max-w-[1800px] grid-cols-1 overflow-hidden rounded-3xl bg-white shadow-2xl lg:grid-cols-2">
                {/* LEFT SIDE */}
                <div className="hidden flex-col items-center justify-center bg-red-800 p-16 text-white lg:flex">
                    <h1 className="text-center text-4xl font-extrabold text-yellow-400">
                        MOA / MOU
                    </h1>

                    <p className="mt-3 text-center text-lg">
                        Partnership Management System
                    </p>

                    <p className="mt-6 max-w-sm text-center text-sm text-red-100">
                        Track agreements, monitor status, and manage
                        partnerships efficiently.
                    </p>
                </div>

                {/* RIGHT SIDE */}
                <div className="flex items-center justify-center p-10 lg:p-16">
                    <div className="w-full max-w-md">
                        <h2 className="text-center text-3xl font-bold text-red-800">
                            {title}
                        </h2>

                        <p className="mt-2 mb-6 text-center text-gray-500">
                            {description}
                        </p>

                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
