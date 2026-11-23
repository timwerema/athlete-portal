/**
 * Make a phone photo smaller in the browser before it is sent: quicker on
 * mobile data and it fits under server upload limits. Drawing to a canvas
 * also drops the photo's hidden data (location and so on). The server still
 * checks and re-saves whatever arrives, so this is a convenience, not the
 * safety check.
 */
export async function shrinkImage(file: File, maxEdge = 1600, quality = 0.88): Promise<File> {
    if (typeof createImageBitmap !== 'function') {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const { width, height } = fitWithin(bitmap.width, bitmap.height, maxEdge);

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');
        if (!context) {
            return file;
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
        context.drawImage(bitmap, 0, 0, width, height);
        bitmap.close();

        const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

        return blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        // Not an image the browser can read. Send it as it is and let the server explain.
        return file;
    }
}

export function fitWithin(width: number, height: number, maxEdge: number): { width: number; height: number } {
    const scale = Math.min(1, maxEdge / Math.max(width, height));

    return { width: Math.max(1, Math.round(width * scale)), height: Math.max(1, Math.round(height * scale)) };
}
