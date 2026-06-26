import React from 'react';

type State = {
    hasError: boolean;
    error?: Error | null;
    info?: React.ErrorInfo | null;
};

export default class ErrorBoundary extends React.Component<{}, State> {
    constructor(props: {}) {
        super(props);
        this.state = { hasError: false, error: null, info: null };
    }

    static getDerivedStateFromError(error: Error) {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo) {
        // You can log the error to an external service here
         
        console.error('ErrorBoundary caught', error, info);
        this.setState({ error, info });
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="rounded-2xl bg-white p-8 shadow">
                    <h2 className="mb-4 text-2xl font-bold text-red-700">
                        An error occurred rendering this page
                    </h2>
                    <div className="text-sm text-gray-700">
                        <p>
                            <strong>Error:</strong> {this.state.error?.message}
                        </p>
                        {this.state.info?.componentStack && (
                            <pre className="mt-4 overflow-auto rounded bg-gray-100 p-3 text-xs">
                                {this.state.info.componentStack}
                            </pre>
                        )}
                    </div>
                </div>
            );
        }

        return this.props.children as React.ReactElement;
    }
}
