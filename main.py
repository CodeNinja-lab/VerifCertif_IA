from fastapi import FastAPI
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
import numpy as np

app = FastAPI()
model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")

# Endpoint racine pour le health check
@app.get("/")
def root():
    return {
        "status": "ok",
        "service": "VeriCertis Embedding API",
        "model": "sentence-transformers/all-MiniLM-L6-v2",
        "version": "1.0.0"
    }

#attend un texte a encodé et renvoie son embedding normalisé
class EmbedRequest(BaseModel):
    text: str
#attend deux embeddings et renvoie leur similarité cosinus
class CosineRequest(BaseModel):
    a: list[float]
    b: list[float]
#reçoit un texte et renvoie son embedding
@app.post("/embed")
def embed(req: EmbedRequest):
    vec = model.encode(req.text, normalize_embeddings=True)  # normalize => cosinus = dot
    return {"embedding": vec.tolist(), "dim": int(vec.shape[0])}
#reçoit deux embeddings et renvoie leur similarité cosinus
@app.post("/cosine")
def cosine(req: CosineRequest):
    a = np.array(req.a, dtype=np.float32)
    b = np.array(req.b, dtype=np.float32)
    # si embeddings normalisés: cos = dot
    score = float(np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b) + 1e-12))
    return {"score": score}